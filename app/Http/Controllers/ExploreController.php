<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExploreController extends BaseController
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $followedCommunityIds = $user?->communities()->pluck('communities.id') ?? collect();

        $posts = Post::with(['community', 'author', 'votes' => function ($q) use ($user) {
                if ($user) {
                    $q->where('user_id', $user->id);
                }
            }])
            ->withCount('comments')
            ->when($followedCommunityIds->count() > 0, function ($q) use ($followedCommunityIds) {
                $q->whereNotIn('community_id', $followedCommunityIds);
            })
            ->orderBy('is_pinned', 'desc')
            ->orderBy('pinned_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $posts->getCollection()->transform(function ($post) use ($user) {
            return $this->transformPost($post, $user?->id);
        });

        return Inertia::render('Explore', [
            'posts' => $posts,
        ]);
    }
}
