<?php

namespace Poniverse\Ponyfm\Repositories;

use Poniverse\Ponyfm\Models\Track;

class TrackRepository
{
    public function findById($id)
    {
        return Track::findOrFail($id);
    }
}
