<?php

namespace App\Http\Middleware;

use App\Enums\ModerationAction;
use App\Models\Community;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ModerationActionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            abort(401, 'Usuário não autenticado');
        }

        try {
            $moderationAction = ModerationAction::from($action);
        } catch (\ValueError $e) {
            abort(400, 'Ação de moderação inválida');
        }

        // Obter a comunidade
        $community = $this->getCommunityFromRequest($request);
        if (!$community) {
            abort(404, 'Comunidade não encontrada');
        }

        // Verificar permissão usando a Policy
        if (!Auth::user()->can('performAction', [$community, $moderationAction])) {
            abort(403, 'Você não tem permissão para realizar esta ação');
        }

        // Verificar se a ação requer confirmação especial
        if ($moderationAction->requiresConfirmation() && !$request->boolean('confirmed')) {
            return response()->json([
                'message' => 'Esta ação requer confirmação',
                'action' => $moderationAction->value,
                'requires_confirmation' => true,
            ], 422);
        }

        // Adicionar informações ao request
        $request->merge([
            'community' => $community,
            'moderation_action' => $moderationAction,
        ]);

        // Rate limiting para ações de moderação (5 ações por minuto)
        $key = 'moderation_actions:' . $user->id . ':' . $community->id;
        if (cache()->has($key)) {
            $count = cache()->get($key, 0);
            if ($count >= 5) {
                abort(429, 'Muitas ações de moderação. Tente novamente em alguns minutos.');
            }
            cache()->increment($key);
        } else {
            cache()->put($key, 1, 60); // 1 minuto
        }

        return $next($request);
    }

    /**
     * Obter a comunidade da requisição
     */
    private function getCommunityFromRequest(Request $request): ?Community
    {
        // Tentar obter da rota
        $community = $request->route('community');
        
        if ($community instanceof Community) {
            return $community;
        }

        // Tentar obter por ID
        if (is_numeric($community)) {
            return Community::find($community);
        }

        // Tentar obter por name/slug
        if (is_string($community)) {
            return Community::where('name', $community)->first();
        }

        // Tentar obter do parâmetro community_id
        $communityId = $request->input('community_id') ?? $request->route('community_id');
        if ($communityId) {
            return Community::find($communityId);
        }

        // Tentar obter do target (post, comment, user)
        $target = $this->getTargetFromRequest($request);
        if ($target) {
            return $this->getCommunityFromTarget($target);
        }

        return null;
    }

    /**
     * Obter o target da ação da requisição
     */
    private function getTargetFromRequest(Request $request)
    {
        // Tentar obter da rota
        $post = $request->route('post');
        if ($post instanceof Post) {
            return $post;
        }

        $comment = $request->route('comment');
        if ($comment instanceof Comment) {
            return $comment;
        }

        $targetUser = $request->route('user');
        if ($targetUser instanceof User) {
            return $targetUser;
        }

        // Tentar obter dos parâmetros
        if ($postId = $request->input('post_id') ?? $request->route('post_id')) {
            return Post::find($postId);
        }

        if ($commentId = $request->input('comment_id') ?? $request->route('comment_id')) {
            return Comment::find($commentId);
        }

        if ($userId = $request->input('user_id') ?? $request->route('user_id')) {
            return User::find($userId);
        }

        return null;
    }

    /**
     * Obter a comunidade do target
     */
    private function getCommunityFromTarget($target): ?Community
    {
        if ($target instanceof Post) {
            return $target->community;
        }

        if ($target instanceof Comment) {
            return $target->post->community;
        }

        if ($target instanceof User) {
            // Para ações de usuário, precisamos da comunidade do contexto
            return null;
        }

        return null;
    }
}