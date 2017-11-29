<?php

namespace Poniverse\Ponyfm\Commands;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\UploadedFile;
use Poniverse\Ponyfm\Models\Track;

class UploadNewTrackVersionCommand implements ShouldQueue
{
    /**
     * @var Track
     */
    private $track;
    /**
     * @var UploadedFile
     */
    private $trackFile;

    public function __construct(
        Track $track,
        UploadedFile $trackFile
    )
    {
        $this->track = $track;
        $this->trackFile = $trackFile;
    }

    /**
     * @return Track
     */
    public function getTrack(): Track
    {
        return $this->track;
    }

    /**
     * @return UploadedFile
     */
    public function getTrackFile(): UploadedFile
    {
        return $this->trackFile;
    }
}
