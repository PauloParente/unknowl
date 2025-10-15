<?php

namespace App\Http\Middleware;

use App\Enums\CommunityRole;
use App\Enums\ModerationAction;
use App\Models\Community;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CommunityModerationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $action, ?string $role = null): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            abort(401, 'Usuário não autenticado');
        }

        // Obter a comunidade da rota
        $community = $this->getCommunityFromRequest($request);
        
        if (!$community) {
            abort(404, 'Comunidade não encontrada');
        }

        // Verificar se usuário tem permissão para a ação
        if (!$this->checkPermission($user, $community, $action, $role)) {
            abort(403, 'Você não tem permissão para realizar esta ação');
        }

        // Adicionar informações da comunidade ao request
        $request->merge(['community' => $community]);

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

        return null;
    }

    /**
     * Verificar se usuário tem permissão para a ação
     */
    private function checkPermission(User $user, Community $community, string $action, ?string $role): bool
    {
        // Verificar se usuário está banido da comunidade
        if ($community->isUserBanned($user)) {
            return false;
        }

        // Verificar se usuário está banido globalmente
        if ($user->isBannedGlobally()) {
            return false;
        }

        $userRole = $community->getUserRole($user);
        
        if (!$userRole) {
            return false;
        }

        // Se foi especificado um role mínimo, verificar hierarquia
        if ($role) {
            $requiredRole = CommunityRole::from($role);
            if (!$userRole->hasHigherAuthorityThan($requiredRole) && $userRole !== $requiredRole) {
                return false;
            }
        }

        // Verificar permissão específica para a ação
        return match ($action) {
            'view' => $this->canView($user, $community),
            'create_post' => $this->canCreatePost($user, $community),
            'create_comment' => $this->canCreateComment($user, $community),
            'moderate' => $this->canModerate($user, $community),
            'manage_moderators' => $this->canManageModerators($user, $community),
            'manage_settings' => $this->canManageSettings($user, $community),
            'ban_users' => $this->canBanUsers($user, $community),
            'pin_posts' => $this->canPinPosts($user, $community),
            'lock_posts' => $this->canLockPosts($user, $community),
            'view_logs' => $this->canViewLogs($user, $community),
            'transfer_ownership' => $this->canTransferOwnership($user, $community),
            'delete_community' => $this->canDeleteCommunity($user, $community),
            default => false,
        };
    }

    private function canView(User $user, Community $community): bool
    {
        if ($community->is_public) {
            return true;
        }

        return $community->hasMember($user);
    }

    private function canCreatePost(User $user, Community $community): bool
    {
        if ($community->isUserBanned($user) || $user->isMutedGlobally()) {
            return false;
        }

        if ($community->is_public) {
            return true;
        }

        return $community->hasMember($user);
    }

    private function canCreateComment(User $user, Community $community): bool
    {
        return $this->canCreatePost($user, $community);
    }

    private function canModerate(User $user, Community $community): bool
    {
        return $userRole = $community->getUserRole($user) && 
               $userRole->hasHigherAuthorityThan(CommunityRole::MEMBER);
    }

    private function canManageModerators(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::ADMIN);
    }

    private function canManageSettings(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::ADMIN);
    }

    private function canBanUsers(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::MODERATOR);
    }

    private function canPinPosts(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::ADMIN);
    }

    private function canLockPosts(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::MODERATOR);
    }

    private function canViewLogs(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::MODERATOR);
    }

    private function canTransferOwnership(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::OWNER);
    }

    private function canDeleteCommunity(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::OWNER);
    }
}