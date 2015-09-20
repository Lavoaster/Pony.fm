<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\ApiControllerBase;
use App\Track;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;

class DashboardController extends ApiControllerBase
{
    public function getIndex()
    {
        $recentQuery = Track::summary()
            ->with(['genre', 'user', 'cover', 'user.avatar'])
            ->whereIsLatest(true)
            ->listed()
            ->userDetails()
            ->explicitFilter()
            ->published()
            ->orderBy('published_at', 'desc')
            ->take(30);

        $recentTracks = [];

        foreach ($recentQuery->get() as $track) {
            $recentTracks[] = Track::mapPublicTrackSummary($track);
        }

        return Response::json([
            'recent_tracks' => $recentTracks,
            'popular_tracks' => Track::popular(30, Auth::check() && Auth::user()->can_see_explicit_content)
        ], 200);
    }
}