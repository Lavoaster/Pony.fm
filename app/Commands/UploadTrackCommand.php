<?php

namespace Poniverse\Ponyfm\Commands;

use Illuminate\Http\UploadedFile;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;

class UploadTrackCommand
{
    const DEFAULT_MIN_DURATION = 30;

    /**
     * @var User
     */
    private $artist;
    /**
     * @var UploadedFile
     */
    private $trackFile;
    /**
     * @var UploadedFile
     */
    private $coverFile;
    /**
     * @var string
     */
    private $source;
    /**
     * @var bool
     */
    private $publishAfterProcessing;
    /**
     * @var bool
     */
    private $lossyAllowed;
    /**
     * @var int
     */
    private $minDuration;
    /**
     * @var null|string
     */
    private $title;
    /**
     * @var int|null
     */
    private $trackTypeId;
    /**
     * @var null|string
     */
    private $genre;
    /**
     * @var null|string
     */
    private $albumName;
    /**
     * @var int|null
     */
    private $trackNumber;
    /**
     * @var null|string
     */
    private $releasedAt;
    /**
     * @var null|string
     */
    private $description;
    /**
     * @var null|string
     */
    private $lyrics;
    /**
     * @var bool|null
     */
    private $isVocal;
    /**
     * @var bool|null
     */
    private $isExplicit;
    /**
     * @var bool|null
     */
    private $isDownloadable;
    /**
     * @var bool|null
     */
    private $isListed;
    /**
     * @var string
     */
    private $metadata;

    public function __construct(
        User $artist,
        UploadedFile $trackFile,
        UploadedFile $coverFile,
        string $source = Track::SOURCE_DIRECT_UPLOAD,
        bool $publishAfterProcessing = false,
        bool $lossyAllowed = false,
        int $minDuration = self::DEFAULT_MIN_DURATION,
        ?string $title = null,
        ?int $trackTypeId = null,
        ?string $genre = null,
        ?string $albumName = null,
        ?int $trackNumber = null,
        ?string $releasedAt = null,
        ?string $description = null,
        ?string $lyrics = null,
        ?bool $isVocal = null,
        ?bool $isExplicit = null,
        ?bool $isDownloadable = null,
        ?bool $isListed = null,
        ?string $metadata = null
    ) {
        $this->artist = $artist;
        $this->trackFile = $trackFile;
        $this->coverFile = $coverFile;
        $this->source = $source;
        $this->publishAfterProcessing = $publishAfterProcessing;
        $this->title = $title;
        $this->trackTypeId = $trackTypeId;
        $this->genre = $genre;
        $this->albumName = $albumName;
        $this->trackNumber = $trackNumber;
        $this->releasedAt = $releasedAt;
        $this->description = $description;
        $this->lyrics = $lyrics;
        $this->isVocal = $isVocal;
        $this->isExplicit = $isExplicit;
        $this->isDownloadable = $isDownloadable;
        $this->isListed = $isListed;
        $this->metadata = $metadata;
        $this->lossyAllowed = $lossyAllowed;
        $this->minDuration = $minDuration;
    }

    /**
     * @return User
     */
    public function getArtist(): User
    {
        return $this->artist;
    }

    /**
     * @return UploadedFile
     */
    public function getTrackFile(): UploadedFile
    {
        return $this->trackFile;
    }

    /**
     * @return UploadedFile
     */
    public function getCoverFile(): UploadedFile
    {
        return $this->coverFile;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return bool
     */
    public function shouldPublishAfterProcessing(): bool
    {
        return $this->publishAfterProcessing;
    }

    /**
     * @return bool
     */
    public function isLossyAllowed(): bool
    {
        return $this->lossyAllowed;
    }

    /**
     * @return int
     */
    public function getMinDuration(): int
    {
        return $this->minDuration;
    }

    /**
     * @return null|string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return int|null
     */
    public function getTrackTypeId()
    {
        return $this->trackTypeId;
    }

    /**
     * @return null|string
     */
    public function getGenre()
    {
        return $this->genre;
    }

    /**
     * @return null|string
     */
    public function getAlbumName()
    {
        return $this->albumName;
    }

    /**
     * @return int|null
     */
    public function getTrackNumber()
    {
        return $this->trackNumber;
    }

    /**
     * @return null|string
     */
    public function getReleasedAt()
    {
        return $this->releasedAt;
    }

    /**
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return null|string
     */
    public function getLyrics()
    {
        return $this->lyrics;
    }

    /**
     * @return bool|null
     */
    public function isVocal()
    {
        return $this->isVocal;
    }

    /**
     * @return bool|null
     */
    public function isExplicit()
    {
        return $this->isExplicit;
    }

    /**
     * @return bool|null
     */
    public function isDownloadable()
    {
        return $this->isDownloadable;
    }

    /**
     * @return bool|null
     */
    public function isListed()
    {
        return $this->isListed;
    }

    /**
     * @return string
     */
    public function getMetadata(): string
    {
        return $this->metadata;
    }
}
