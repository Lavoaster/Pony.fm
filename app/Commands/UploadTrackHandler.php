<?php

namespace Poniverse\Ponyfm\Commands;

use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Repositories\TrackRepository;

class UploadTrackHandler
{
    /**
     * @var TrackRepository
     */
    private $trackRepository;

    public function __construct(
        TrackRepository $trackRepository
    ) {
        $this->trackRepository = $trackRepository;
    }

    public function handle(UploadTrackCommand $command)
    {
        $trackFile = $command->getTrackFile();
        $audio = \AudioCache::get($trackFile->getPathname());

        $track = new Track();
        $track->user_id = $command->getArtist()->id;
        $track->title = mb_strimwidth($command->getTitle() ?: pathinfo($trackFile->getClientOriginalName(), PATHINFO_FILENAME), 0, 100, '...');
        $track->duration = $audio->getDuration();
        $track->current_version = 1;
        $track->source = $command->getSource();
        $track->save();

        $track->ensureDirectoryExists();

        //  TODO: Turn this into an artisan command, this shouldn't be ran in every upload

        if (!is_dir(config('ponyfm.files_directory').'/tmp')) {
            mkdir(config('ponyfm.files_directory').'/tmp', 0755, true);
        }

        if (!is_dir(config('ponyfm.files_directory').'/queued-tracks')) {
            mkdir(config('ponyfm.files_directory').'/queued-tracks', 0755, true);
        }

        $trackFile->move(
            config('ponyfm.files_directory') . '/queued-tracks',
            "{$track->title}v{$track->current_version}"
        );

        // VALIDATION CODE

    }
}
