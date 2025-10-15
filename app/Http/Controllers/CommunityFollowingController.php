<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunityFollowingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $communities = $user->communities()
            ->select('communities.id', 'communities.name', 'communities.title', 'communities.avatar')
            ->orderBy('communities.name')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => (str_starts_with($c->name, 'r/') ? $c->name : 'r/' . $c->name),
                    'title' => $c->title,
                    'avatar' => $c->avatar,
                ];
            })
            ->values();

        return Inertia::render('communities/Following', [
            'communities' => $communities,
            'count' => $communities->count(),
        ]);
    }
}


