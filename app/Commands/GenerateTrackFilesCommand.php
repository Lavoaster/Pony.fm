<?php

namespace Poniverse\Ponyfm\Commands;

use Poniverse\Ponyfm\Models\Track;

class GenerateTrackFilesCommand
{
    /**
     * @var Track
     */
    private $track;
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
    /**
     * @var int
     */
    private $version;

    public function __construct(
        Track $track,
        string $filePath,
        bool $isExpirable,
        bool $isForUpload,
        bool $autoPublishWhenComplete,
        bool $isReplacingTrack,
        int $version = 1
    ) {
        $this->track = $track;
        $this->filePath = $filePath;
        $this->isExpirable = $isExpirable;
        $this->isForUpload = $isForUpload;
        $this->autoPublishWhenComplete = $autoPublishWhenComplete;
        $this->isReplacingTrack = $isReplacingTrack;
        $this->version = $version;
    }

    /**
     * @return Track
     */
    public function getTrack(): Track
    {
        return $this->track;
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

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }
}
