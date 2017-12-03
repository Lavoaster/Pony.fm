<?php

namespace Poniverse\Ponyfm\Commands;

use Poniverse\Ponyfm\Models\TrackFile;

class PlzGenerateTrackFilesCommand
{
    /**
     * @var TrackFile
     */
    private $trackFile;
    /**
     * @var string
     */
    private $filePath;
    /**
     * @var bool
     */
    private $isExpirable;
    /**
     * @var bool
     */
    private $isForUpload;
    /**
     * @var bool
     */
    private $autoPublishWhenComplete;
    /**
     * @var bool
     */
    private $isReplacingTrack;

    public function __construct(
        TrackFile $trackFile,
        string $filePath,
        bool $isExpirable,
        bool $isForUpload,
        bool $autoPublishWhenComplete,
        bool $isReplacingTrack
    ) {
        $this->trackFile = $trackFile;
        $this->filePath = $filePath;
        $this->isExpirable = $isExpirable;
        $this->isForUpload = $isForUpload;
        $this->autoPublishWhenComplete = $autoPublishWhenComplete;
        $this->isReplacingTrack = $isReplacingTrack;
    }

    /**
     * @return TrackFile
     */
    public function getTrackFile(): TrackFile
    {
        return $this->trackFile;
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return bool
     */
    public function isExpirable(): bool
    {
        return $this->isExpirable;
    }

    /**
     * @return bool
     */
    public function isForUpload(): bool
    {
        return $this->isForUpload;
    }

    /**
     * @return bool
     */
    public function isAutoPublishWhenComplete(): bool
    {
        return $this->autoPublishWhenComplete;
    }

    /**
     * @return bool
     */
    public function isReplacingTrack(): bool
    {
        return $this->isReplacingTrack;
    }
}
