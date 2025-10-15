<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends BaseController
{
    /**
     * Display the specified user's profile.
     */
    public function show(Request $request, string $username): Response
    {
        $user = User::where('name', $username)->firstOrFail();

        // Buscar posts do usuário com paginação
        $requestUser = $request->user();
        $posts = Post::with(['community', 'author', 'votes' => function ($q) use ($requestUser) {
                if ($requestUser) {
                    $q->where('user_id', $requestUser->id);
                }
            }])
            ->withCount('comments')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Transformar dados para corresponder aos tipos do frontend
        $posts->getCollection()->transform(function ($post) use ($requestUser) {
            return $this->transformPost($post, $requestUser?->id);
        });

        // Calcular estatísticas do usuário
        $stats = [
            'posts_count' => Post::where('user_id', $user->id)->count(),
            'communities_count' => $user->communities()->count(),
            'total_score' => Post::where('user_id', $user->id)->sum('score'),
        ];

        return Inertia::render('users/Show', [
            'user' => $user,
            'posts' => $posts,
            'stats' => $stats,
        ]);
    }
}
