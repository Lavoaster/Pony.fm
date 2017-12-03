<?php

namespace Poniverse\Ponyfm\Commands;

use AudioCache;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\TrackFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GenerateTrackFilesHandler
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

    protected static $_losslessFormats = [
        'flac',
        'pcm',
        'adpcm',
        'alac'
    ];

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
        try {
            $this->generateTrack($command);
        } catch (\Exception $e) {
            if ($command->isReplacingTrack()) {
                $this->track->version_upload_status = Track::STATUS_ERROR;
                $this->track->update();
            } else {
                $this->track->delete();
            }
            throw $e;
        }
    }

    private function generateTrack(GenerateTrackFilesCommand $command)
    {
        $source = $command->getFilePath();

        // Lossy uploads need to be identified and set as the master file
        // without being re-encoded.
        $audioObject = AudioCache::get($source);
        $isLossyUpload = !$this->isLosslessFile($audioObject);
        $codecString = $audioObject->getAudioCodec();

        if ($isLossyUpload) {
            if ($codecString === 'mp3') {
                $masterFormat = 'MP3';
            } else if (Str::startsWith($codecString, 'aac')) {
                $masterFormat = 'AAC';
            } else if ($codecString === 'vorbis') {
                $masterFormat = 'OGG Vorbis';
            } else {
                $this->track->delete();
                return CommandResponse::fail(['track' => "The track does not contain audio in a known lossy format. The format read from the file is: {$codecString}"]);
            }

            // Sanity check: skip creating this TrackFile if it already exists.
            $trackFile = $this->trackFileExists($masterFormat);

            if (!$trackFile) {
                $trackFile = new TrackFile();
                $trackFile->is_master = true;
                $trackFile->format = $masterFormat;
                $trackFile->track_id = $this->track->id;
                $trackFile->version = $this->version;
                $trackFile->save();
            }

            // Lossy masters are copied into the datastore - no re-encoding involved.
            File::copy($source, $trackFile->getFile());
        }


        $trackFiles = [];

        foreach (Track::$Formats as $name => $format) {
            // Don't bother with lossless transcodes of lossy uploads, and
            // don't re-encode the lossy master.
            if ($isLossyUpload && ($format['is_lossless'] || $name === $masterFormat)) {
                continue;
            }

            // Sanity check: skip creating this TrackFile if it already exists.
            //               But, we'll still encode it!
            if ($trackFile = $this->trackFileExists($name)) {
                $trackFiles[] = $trackFile;
                continue;
            }

            $trackFile = new TrackFile();
            $trackFile->is_master = $name === 'FLAC' ? true : false;
            $trackFile->format = $name;
            $trackFile->status = TrackFile::STATUS_PROCESSING_PENDING;
            $trackFile->version = $this->version;

            if (in_array($name, Track::$CacheableFormats) && !$trackFile->is_master) {
                $trackFile->is_cacheable = true;
            } else {
                $trackFile->is_cacheable = false;
            }
            $this->track->trackFilesForAllVersions()->save($trackFile);

            // All TrackFile records we need are synchronously created
            // before kicking off the encode jobs in order to avoid a race
            // condition with the "temporary" source file getting deleted.
            $trackFiles[] = $trackFile;
        }

        try {
            foreach ($trackFiles as $trackFile) {
                // Don't re-encode master files when replacing tracks with an already-uploaded version
                if ($trackFile->is_master && !$this->isForUpload && $this->isReplacingTrack) {
                    continue;
                }
                $this->dispatch(new EncodeTrackFile($trackFile, false, false, $this->isForUpload, $this->isReplacingTrack));
            }
        } catch (InvalidEncodeOptionsException $e) {
            // Only delete the track if the track is not being replaced
            if ($this->isReplacingTrack) {
                $this->track->version_upload_status = Track::STATUS_ERROR;
                $this->track->update();
            } else {
                $this->track->delete();
            }
            return CommandResponse::fail(['track' => [$e->getMessage()]]);
        }
    }

    /**
     * @param FFmpegMovie|string $file object or full path of the file we're checking
     * @return bool whether the given file is lossless
     */
    private function isLosslessFile($file)
    {
        if (is_string($file)) {
            $file = AudioCache::get($file);
        }

        return Str::startsWith($file->getAudioCodec(), static::$_losslessFormats);
    }

    /**
     * @param string $format
     * @return TrackFile|null
     */
    private function trackFileExists(string $format)
    {
        return $this->track->trackFilesForAllVersions()->where('format', $format)->where('version', $this->version)->first();
    }
}
