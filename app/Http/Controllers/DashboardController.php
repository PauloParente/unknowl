<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends BaseController
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $followedCommunityIds = $user?->communities()->pluck('communities.id');

        $query = Post::with(['community', 'author', 'votes' => function ($q) use ($user) {
                if ($user) {
                    $q->where('user_id', $user->id);
                }
            }])
            ->withCount('comments')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('pinned_at', 'desc')
            ->orderBy('created_at', 'desc');

        if ($followedCommunityIds && $followedCommunityIds->count() > 0) {
            $query->whereIn('community_id', $followedCommunityIds);
        } else {
            // If user follows none, redirect to onboarding suggestions
            return redirect()->route('onboarding.suggestions');
        }

        $posts = $query->paginate(20);

        // Transformar dados para corresponder aos tipos do frontend
        $posts->getCollection()->transform(function ($post) use ($user) {
            return $this->transformPost($post, $user?->id);
        });

        return Inertia::render('Dashboard', [
            'posts' => $posts,
        ]);
    }
}
