<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends BaseController
{
    public function index(Request $request): Response
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all');
        $user = $request->user();

        if (empty(trim($query))) {
            return Inertia::render('Search', [
                'results' => [
                    'communities' => [],
                    'posts' => [],
                    'users' => [],
                ],
                'query' => $query,
                'type' => $type,
                'total' => 0,
            ]);
        }

        $results = [
            'communities' => collect(),
            'posts' => collect(),
            'users' => collect(),
        ];

        // Buscar comunidades
        if ($type === 'all' || $type === 'communities') {
            $results['communities'] = $this->searchCommunities($query, $user);
        }

        // Buscar posts
        if ($type === 'all' || $type === 'posts') {
            $results['posts'] = $this->searchPosts($query, $user);
        }

        // Buscar usuários
        if ($type === 'all' || $type === 'users') {
            $results['users'] = $this->searchUsers($query, $user);
        }

        $total = $results['communities']->count() + $results['posts']->count() + $results['users']->count();

        return Inertia::render('Search', [
            'results' => $results,
            'query' => $query,
            'type' => $type,
            'total' => $total,
        ]);
    }

    public function autocomplete(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'suggestions' => []
            ]);
        }

        $suggestions = collect();

        // Buscar comunidades (limitado a 5)
        $communities = Community::where('name', 'like', "%{$query}%")
            ->orWhere('title', 'like', "%{$query}%")
            ->limit(3)
            ->get()
            ->map(function ($community) {
                return [
                    'type' => 'community',
                    'id' => $community->id,
                    'name' => $community->name,
                    'title' => $community->title,
                    'avatar' => $community->avatar,
                    'url' => route('communities.show', $community),
                ];
            });

        // Buscar usuários (limitado a 3)
        $users = User::where('name', 'like', "%{$query}%")
            ->limit(2)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'user',
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'url' => route('users.show', $user),
                ];
            });

        $suggestions = $suggestions->merge($communities)->merge($users);

        return response()->json([
            'suggestions' => $suggestions->take(5)->values()
        ]);
    }

    private function searchCommunities(string $query, ?User $user)
    {
        return Community::where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->withCount('members')
            ->withCount('posts')
            ->when($user, function ($q) use ($user) {
                $q->with(['members' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }]);
            })
            ->orderBy('members_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($community) use ($user) {
                return [
                    'id' => $community->id,
                    'name' => $community->name,
                    'title' => $community->title,
                    'description' => $community->description,
                    'avatar' => $community->avatar,
                    'cover_url' => $community->cover_url,
                    'members_count' => $community->members_count,
                    'posts_count' => $community->posts_count,
                    'is_following' => $user ? $community->members->isNotEmpty() : false,
                    'url' => route('communities.show', $community),
                ];
            });
    }

    private function searchPosts(string $query, ?User $user)
    {
        return Post::where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('text', 'like', "%{$query}%");
            })
            ->with(['community', 'author', 'votes' => function ($q) use ($user) {
                if ($user) {
                    $q->where('user_id', $user->id);
                }
            }])
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($post) use ($user) {
                return $this->transformPost($post, $user?->id);
            });
    }

    private function searchUsers(string $query, ?User $user)
    {
        return User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->withCount('posts')
            ->withCount('comments')
            ->orderBy('posts_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($userResult) {
                return [
                    'id' => $userResult->id,
                    'name' => $userResult->name,
                    'email' => $userResult->email,
                    'avatar' => $userResult->avatar,
                    'cover_url' => $userResult->cover_url,
                    'bio' => $userResult->bio,
                    'posts_count' => $userResult->posts_count,
                    'comments_count' => $userResult->comments_count,
                    'url' => route('users.show', $userResult),
                ];
            });
    }
}
