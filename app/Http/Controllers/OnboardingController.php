<?php

namespace App\Http\Controllers;

use App\Models\Community;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function suggestions(Request $request): Response
    {
        $user = $request->user();

        $followedIds = $user?->communities()->pluck('communities.id') ?? collect();

        $suggestions = Community::query()
            ->when($followedIds->count() > 0, fn ($q) => $q->whereNotIn('id', $followedIds))
            ->select('id', 'name', 'title', 'avatar')
            ->orderBy('name')
            ->limit(24)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => 'r/' . $c->name,
                    'title' => $c->title,
                    'avatar' => $c->avatar,
                ];
            })
            ->values();

        return Inertia::render('OnboardingSuggestions', [
            'suggestions' => $suggestions,
        ]);
    }
}


