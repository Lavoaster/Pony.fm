<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Peter Deltchev
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Poniverse\Ponyfm\Commands\Old;

use AudioCache;
use FFmpegMovie;
use File;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Str;
use Poniverse\Ponyfm\Exceptions\InvalidEncodeOptionsException;
use Poniverse\Ponyfm\Jobs\EncodeTrackFile;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\TrackFile;
use SplFileInfo;

/**
 * This command is the "second phase" of the upload process - once metadata has
 * been parsed and the track object is created, this generates the track's
 * corresponding TrackFile objects and ensures that all of them have been encoded.
 *
 * @package Poniverse\Ponyfm\Commands
 */
class GenerateTrackFilesCommand extends CommandBase
{
    use DispatchesJobs;

    private $track;
    private $autoPublish;
    private $sourceFile;
    private $isForUpload;
    private $isReplacingTrack;
    private $version;

    protected static $_losslessFormats = [
        'flac',
        'pcm',
        'adpcm',
        'alac'
    ];

    public function __construct(Track $track, SplFileInfo $sourceFile, bool $autoPublish = false, bool $isForUpload = false, bool $isReplacingTrack = false, int $version = 1)
    {
        $this->track = $track;
        $this->autoPublish = $autoPublish;
        $this->sourceFile = $sourceFile;
        $this->isForUpload = $isForUpload;
        $this->isReplacingTrack = $isReplacingTrack;
        $this->version = $version;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        try {

        } catch (\Exception $e) {
            if ($this->isReplacingTrack) {
                $this->track->version_upload_status = Track::STATUS_ERROR;
                $this->track->update();
            } else {
                $this->track->delete();
            }
            throw $e;
        }

        // This ensures that any updates to the track record, like from parsed
        // tags, are reflected in the command's response.
        $this->track = $this->track->fresh();
        return CommandResponse::succeed([
            'id' => $this->track->id,
            'name' => $this->track->name,
            'title' => $this->track->title,
            'slug' => $this->track->slug,
            'autoPublish' => $this->autoPublish,
        ]);
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
