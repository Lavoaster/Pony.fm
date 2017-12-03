<?php

namespace Poniverse\Ponyfm\Commands;

use Carbon\Carbon;
use Illuminate\Auth\Access\Gate;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use League\Tactician\CommandBus;
use Poniverse\Ponyfm\Models\Image;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\TrackType;
use Poniverse\Ponyfm\Util\TagParser;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UploadTrackHandler
{
    /**
     * @var Gate
     */
    private $gate;
    /**
     * @var ValidationFactory
     */
    private $validationFactory;
    /**
     * @var CommandBus
     */
    private $bus;
    /**
     * @var TagParser
     */
    private $tagParser;

    public function __construct(
        Gate $gate,
        ValidationFactory $validationFactory,
        CommandBus $bus,
        TagParser $tagParser
    ) {
        $this->gate = $gate;
        $this->validationFactory = $validationFactory;
        $this->bus = $bus;
        $this->tagParser = $tagParser;
    }

    public function handle(UploadTrackCommand $command)
    {
        $this->validate($command);

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

        $trackFile->move(
            config('ponyfm.files_directory') . '/queued-tracks',
            "{$track->title}v{$track->current_version}"
        );

        list($parsedTags, $rawTags) = $this->tagParser->parseOriginalTags($trackFile, $command->getArtist(), $audio->getAudioCodec());

        $track->original_tags = ['parsed_tags' => $parsedTags, 'raw_tags' => $rawTags];

        $track->cover_id = $parsedTags['cover_id'];

        if ($command->getCoverFile() ?? false) {
            $track->cover_id = Image::upload($command->getCoverFile(), $command->getArtist())->id;
        }

        $track->title = $command->getTitle() ?: ($parsedTags['title'] ?? $track->title);
        $track->track_type_id = $command->getTrackTypeId() ?: TrackType::UNCLASSIFIED_TRACK;

        $track->genre_id = $command->getGenre()
            ? $this->tagParser->getGenreId($command->getGenre())
            : $parsedTags['genre_id'];

        $track->album_id = $command->getAlbumName()
            ? $this->tagParser->getAlbumId($track->user_id, $command->getAlbumName())
            : $parsedTags['album_id'];
        $track->track_number = null;

        if ($track->album_id !== null) {
            $track->track_number = filter_var($command->getTrackNumber() ?: $parsedTags['track_number'], FILTER_SANITIZE_NUMBER_INT);

            if ($track->track_number === null) {
                $track->track_number = 1;
            }
        }

        $track->released_at = $command->getReleasedAt()
            ? Carbon::createFromFormat(Carbon::ISO8601, $command->getReleasedAt())
            : $parsedTags['release_date'];

        $track->description = $command->getDescription() ?: $parsedTags['comments'];
        $track->lyrics = $command->getLyrics() ?: $parsedTags['lyrics'];

        $track->is_vocal = $command->isVocal() ?: $parsedTags['is_vocal'];
        $track->is_explicit = $command->isExplicit() ?: false;
        $track->is_downloadable = $command->isDownloadable() ?: true;
        $track->is_listed = $command->isListed() ?: true;

        $vars = $track->getAttributes();

        foreach ($vars as $key => $value) {
            if ($value === null) {
                unset($track->{"$key"});
            }
        }

        $track->save();

        $this->bus->handle(new GenerateTrackFilesCommand(

        ));

        return $track;
    }

    /**
     * @param UploadTrackCommand $command
     * @throws ValidationException
     * @throws AccessDeniedHttpException
     */
    private function validate(UploadTrackCommand $command)
    {
        // Auth Validation

        if (!$this->gate->allows('create-track', $command->getArtist())) {
            throw new AccessDeniedHttpException('User does not have permission to create track');
        }

        // Data Validation
        $trackValidation = [
            'required',
            'audio_format:flac,alac,pcm,adpcm' . ($command->isLossyAllowed() ? 'aac,mp3,vorbis' : ''),
            'audio_channels:1,2',
        ];

        if ($command->getMinDuration()) {
            $trackValidation[] = 'min_duration:' . $command->getMinDuration();
        }

        $rules = [
            'track'           => implode('|', $trackValidation),
            'cover'           => 'image|mimes:png,jpeg|min_width:350|min_height:350',
            'auto_publish'    => 'boolean',
            'title'           => 'string',
            'track_type_id'   => 'exists:track_types,id',
            'genre'           => 'string',
            'album'           => 'string',
            'track_number'    => 'integer',
            'released_at'     => 'date_format:' . Carbon::ATOM,
            'description'     => 'string',
            'lyrics'          => 'string',
            'is_vocal'        => 'boolean',
            'is_explicit'     => 'boolean',
            'is_downloadable' => 'boolean',
            'is_listed'       => 'boolean',
            'metadata'        => 'json'
        ];

        $data = [
            'track'           => $command->getTrackFile(),
            'cover'           => $command->getCoverFile(),
            'auto_publish'    => $command->shouldPublishAfterProcessing(),
            'title'           => $command->getTitle(),
            'track_type_id'   => $command->getTrackTypeId(),
            'genre'           => $command->getGenre(),
            'album'           => $command->getAlbumName(),
            'track_number'    => $command->getTrackNumber(),
            'released_at'     => $command->getReleasedAt(),
            'description'     => $command->getDescription(),
            'lyrics'          => $command->getLyrics(),
            'is_vocal'        => $command->isVocal(),
            'is_explicit'     => $command->isExplicit(),
            'is_downloadable' => $command->isDownloadable(),
            'is_listed'       => $command->isListed(),
            'metadata'        => $command->getMetadata(),
        ];

        $validator = $this->validationFactory->make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
