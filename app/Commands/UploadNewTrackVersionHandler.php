<?php

namespace Poniverse\Ponyfm\Commands;

use Poniverse\Ponyfm\Models\Track;

class UploadNewTrackVersionHandler
{
    public function handle(UploadNewTrackVersionCommand $command)
    {
        $track = $command->getTrack();
        $track->version_upload_status = Track::STATUS_ERROR;
        $track->update();

        // VALIDATE

        // FIRE Gen Track Files
    }
}
