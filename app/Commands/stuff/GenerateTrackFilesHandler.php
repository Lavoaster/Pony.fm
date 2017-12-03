<?php

namespace Poniverse\Ponyfm\Commands;

use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\Filesystem;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\TrackFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PlzGenerateTrackFilesHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var DatabaseManager
     */
    private $database;

    public function __construct(
        LoggerInterface $logger,
        Filesystem $filesystem,
        DatabaseManager $database
    ) {
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->database = $database;
    }

    public function handle(GenerateTrackFilesCommand $command)
    {
        $trackFile = $command->getTrack();
        
        // Sanity check: was this file just generated, or is it already being processed?
        if ($trackFile->status === TrackFile::STATUS_PROCESSING) {
            $this->logger->warning('Track file #'.$trackFile->id.' (track #'.$trackFile->track_id.') is already being processed!');
            return;
        }

        if (!$trackFile->is_expired && $this->filesystem->exists($trackFile->getFile())) {
            $this->logger->warning('Track file #'.$trackFile->id.' (track #'.$trackFile->track_id.') is still valid! No need to re-encode it.');
            return;
        }

        // Start the job
        $trackFile->status = TrackFile::STATUS_PROCESSING;
        $trackFile->save();

        // Use the track's master file as the source
        if ($command->isForUpload()) {
            $source = $trackFile->track->getTemporarySourceFileForVersion($trackFile->version);
        } else {
            $source = TrackFile::where('track_id', $trackFile->track_id)
                ->where('is_master', true)
                ->where('version', $trackFile->version)
                ->first()
                ->getFile();
        }

        // Assign the target
        $trackFile->track->ensureDirectoryExists();
        $target = $trackFile->getFile();

        // Prepare the command
        $format = Track::$Formats[$trackFile->format];
        $commandline = $format['command'];
        $commandline = str_replace(['{$source}', '{$target}'], ['"' . $source . '"', '"' . $target . '"'], $commandline);

        $this->logger->info('Encoding track file ' . $trackFile->id . ' into ' . $target);

        // Start a synchronous process to encode the file
        $process = new Process($commandline);
        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            $this->logger->error('An exception occurred in the encoding process for track file ' . $trackFile->id . ' - ' . $e->getMessage());
            $this->logger->info($process->getOutput());
            // Ensure queue fails
            throw $e;
        }

        // Update the tags of the track
        $trackFile->track->updateTags($trackFile->format);

        // Insert the expiration time for cached tracks
        if ($command->isExpirable() && $trackFile->is_cacheable) {
            $trackFile->expires_at = Carbon::now()->addMinutes(config('ponyfm.track_file_cache_duration'));
            $trackFile->save();
        }

        // Update file size
        $trackFile->updateFilesize();

        // Complete the job
        $trackFile->status = TrackFile::STATUS_NOT_BEING_PROCESSED;
        $trackFile->save();

        if ($command->isForUpload() || $command->isReplacingTrack()) {
            if (!$trackFile->is_master && $trackFile->is_cacheable) {
                $this->filesystem->delete($trackFile->getFile());
            }

            // This was the final TrackFile for this track!
            if ($trackFile->track->status === Track::STATUS_COMPLETE) {
                if ($command->isAutoPublishWhenComplete()) {
                    $trackFile->track->published_at = Carbon::now();
                    $this->database->table('tracks')
                        ->where('user_id', $trackFile->track->user_id)
                        ->update(['is_latest' => false]);

                    $trackFile->track->is_latest = true;
                    $trackFile->track->save();
                }

                if ($command->isReplacingTrack()) {
                    $oldVersion = $trackFile->track->current_version;

                    // Update the version of the track being uploaded
                    $trackFile->track->duration = \AudioCache::get($source)->getDuration();
                    $trackFile->track->current_version = $trackFile->version;
                    $trackFile->track->version_upload_status = Track::STATUS_COMPLETE;
                    $trackFile->track->update();

                    // Delete the non-master files for the old version
                    if ($oldVersion !== $trackFile->version) {
                        $trackFilesToDelete = $trackFile->track
                            ->trackFilesForVersion($oldVersion)
                            ->where('is_master', false)
                            ->get();

                        foreach ($trackFilesToDelete as $trackFileToDelete) {
                            if ($this->filesystem->exists($trackFileToDelete->getFile())) {
                                $this->filesystem->delete($trackFileToDelete->getFile());
                            }
                        }
                    }
                }

                if ($command->isForUpload()) {
                    $this->filesystem->delete($trackFile->track->getTemporarySourceFileForVersion($trackFile->version));
                }
            }
        }
    }
}
