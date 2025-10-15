<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Post;
use App\Models\Comment;

class EnsureUserCanVote
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Log para debug
        \Log::info('EnsureUserCanVote middleware executado', [
            'user_id' => $user?->id,
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'url' => $request->url(),
        ]);
        
        // Verificar se o usuário está autenticado
        if (!$user) {
            \Log::warning('Usuário não autenticado tentando votar');
            return response()->json([
                'success' => false,
                'message' => 'Você precisa estar logado para votar',
            ], 401);
        }
        
        // Verificar se o usuário não é o autor do post/comentário
        if ($request->route('post')) {
            $post = $request->route('post');
            if ($post && $post->user_id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não pode votar em seu próprio post',
                ], 403);
            }
        }
        
        if ($request->route('comment')) {
            $comment = $request->route('comment');
            if ($comment && $comment->user_id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não pode votar em seu próprio comentário',
                ], 403);
            }
        }
        
        return $next($request);
    }
}
