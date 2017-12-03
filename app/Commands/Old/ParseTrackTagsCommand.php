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

use Carbon\Carbon;
use Config;
use getID3;
use Poniverse\Ponyfm\Models\Album;
use Poniverse\Ponyfm\Models\Genre;
use Poniverse\Ponyfm\Models\Image;
use Poniverse\Ponyfm\Models\Track;
use AudioCache;
use File;
use Illuminate\Support\Str;
use Poniverse\Ponyfm\Models\TrackType;
use Poniverse\Ponyfm\Models\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ParseTrackTagsCommand extends CommandBase
{
    private $track;
    private $fileToParse;
    private $input;

    public function __construct(Track $track, \Symfony\Component\HttpFoundation\File\File $fileToParse, $inputTags = [])
    {
        $this->track = $track;
        $this->fileToParse = $fileToParse;
        $this->input = $inputTags;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $audio = AudioCache::get($this->fileToParse->getPathname());
        list($parsedTags, $rawTags) = $this->parseOriginalTags($this->fileToParse, $this->track->user, $audio->getAudioCodec());
        $this->track->original_tags = ['parsed_tags' => $parsedTags, 'raw_tags' => $rawTags];


        if ($this->input['cover'] ?? false) {
            $this->track->cover_id = Image::upload($this->input['cover'], $this->track->user_id)->id;
        } else {
            $this->track->cover_id = $parsedTags['cover_id'];
        }

        $this->track->title           = $this->input['title'] ?? $parsedTags['title'] ?? $this->track->title;
        $this->track->track_type_id   = $this->input['track_type_id'] ?? TrackType::UNCLASSIFIED_TRACK;

        $this->track->genre_id = isset($this->input['genre'])
            ? $this->getGenreId($this->input['genre'])
            : $parsedTags['genre_id'];

        $this->track->album_id = isset($this->input['album'])
            ? $this->getAlbumId($this->track->user_id, $this->input['album'])
            : $parsedTags['album_id'];

        if ($this->track->album_id === null) {
            $this->track->track_number = null;
        } else {
            $this->track->track_number = filter_var($this->input['track_number'] ?? $parsedTags['track_number'], FILTER_SANITIZE_NUMBER_INT);
            if ($this->track->track_number === null) {
                $this->track->track_number = 1;
            }
        }

        $this->track->released_at = isset($this->input['released_at'])
            ? Carbon::createFromFormat(Carbon::ISO8601, $this->input['released_at'])
            : $parsedTags['release_date'];

        $this->track->description     = $this->input['description'] ?? $parsedTags['comments'];
        $this->track->lyrics          = $this->input['lyrics'] ?? $parsedTags['lyrics'];

        $this->track->is_vocal        = $this->input['is_vocal'] ?? $parsedTags['is_vocal'];
        $this->track->is_explicit     = $this->input['is_explicit'] ?? false;
        $this->track->is_downloadable = $this->input['is_downloadable'] ?? true;
        $this->track->is_listed       = $this->input['is_listed'] ?? true;

        $this->track = $this->unsetNullVariables($this->track);

        $this->track->save();
        return CommandResponse::succeed();
    }

    /**
     * If a value is null, remove it! Helps prevent weird SQL errors
     *
     * @param Track
     * @return Track
    */
    private function unsetNullVariables($track)
    {
        $vars = $track->getAttributes();

        foreach ($vars as $key => $value) {
            if ($value === null) {
                unset($track->{"$key"});
            }
        }

        return $track;
    }
}
