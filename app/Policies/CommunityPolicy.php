<?php

namespace App\Policies;

use App\Enums\CommunityRole;
use App\Models\Community;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommunityPolicy
{
    use HandlesAuthorization;

    /**
     * Verificar se usuário pode visualizar a comunidade
     */
    public function view(User $user, Community $community): bool
    {
        // Se a comunidade é pública, qualquer um pode ver
        if ($community->is_public) {
            return true;
        }

        // Se é privada, apenas membros podem ver
        return $community->hasMember($user);
    }

    /**
     * Verificar se usuário pode editar configurações básicas da comunidade
     */
    public function updateSettings(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::ADMIN);
    }

    /**
     * Verificar se usuário pode editar regras da comunidade
     */
    public function updateRules(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::ADMIN);
    }

    /**
     * Verificar se usuário pode gerenciar posts fixados da comunidade
     */
    public function updatePinnedPosts(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::ADMIN);
    }

    /**
     * Verificar se usuário pode gerenciar moderadores
     */
    public function manageModerators(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::ADMIN);
    }

    /**
     * Verificar se usuário pode moderar conteúdo (posts, comentários)
     */
    public function moderateContent(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::MODERATOR);
    }

    /**
     * Verificar se usuário pode transferir propriedade da comunidade
     */
    public function transferOwnership(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::OWNER);
    }

    /**
     * Verificar se usuário pode deletar a comunidade
     */
    public function delete(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::OWNER);
    }

    /**
     * Verificar se usuário pode gerenciar membros (aprovar, remover)
     */
    public function manageMembers(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::MODERATOR);
    }

    /**
     * Verificar se usuário pode fixar/desfixar posts
     */
    public function pinPosts(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::ADMIN);
    }

    /**
     * Verificar se usuário pode bloquear posts
     */
    public function lockPosts(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::MODERATOR);
    }

    /**
     * Verificar se usuário pode banir outros usuários
     */
    public function banUsers(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::MODERATOR);
    }

    /**
     * Verificar se usuário pode desbanir outros usuários
     */
    public function unbanUsers(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::MODERATOR);
    }

    /**
     * Verificar se usuário pode ver logs de moderação
     */
    public function viewModerationLogs(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::MODERATOR);
    }

    /**
     * Verificar se usuário pode gerenciar um moderador específico
     */
    public function manageModerator(User $user, Community $community, User $targetModerator): bool
    {
        // Usuário deve ter permissão para gerenciar moderadores
        if (!$this->manageModerators($user, $community)) {
            return false;
        }

        // Verificar se pode gerenciar o moderador específico
        return $community->userCanManage($user, $targetModerator);
    }

    /**
     * Verificar se usuário pode gerenciar um membro específico
     */
    public function manageMember(User $user, Community $community, User $targetMember): bool
    {
        // Usuário deve ter permissão para gerenciar membros
        if (!$this->manageMembers($user, $community)) {
            return false;
        }

        // Verificar se pode gerenciar o membro específico
        return $community->userCanManage($user, $targetMember);
    }

    /**
     * Verificar se usuário pode criar posts na comunidade
     */
    public function createPost(User $user, Community $community): bool
    {
        // Verificar se usuário está banido
        if ($community->isUserBanned($user)) {
            return false;
        }

        // Verificar se usuário está mutado globalmente
        if ($user->isMutedGlobally()) {
            return false;
        }

        // Se a comunidade é pública, qualquer um pode postar
        if ($community->is_public) {
            return true;
        }

        // Se é privada, apenas membros podem postar
        return $community->hasMember($user);
    }

    /**
     * Verificar se usuário pode comentar em posts da comunidade
     */
    public function createComment(User $user, Community $community): bool
    {
        // Mesmas regras que criar posts
        return $this->createPost($user, $community);
    }

    /**
     * Verificar se usuário pode seguir a comunidade
     */
    public function follow(User $user, Community $community): bool
    {
        // Não pode seguir se já é membro
        if ($community->hasMember($user)) {
            return false;
        }

        // Não pode seguir se está banido
        if ($community->isUserBanned($user)) {
            return false;
        }

        // Qualquer usuário pode tentar seguir
        return true;
    }

    /**
     * Verificar se usuário pode deixar de seguir a comunidade
     */
    public function unfollow(User $user, Community $community): bool
    {
        // Apenas membros podem deixar de seguir
        return $community->hasMember($user);
    }

    /**
     * Verificar se usuário pode reportar conteúdo da comunidade
     */
    public function report(User $user, Community $community): bool
    {
        // Qualquer usuário autenticado pode reportar
        return true;
    }

    /**
     * Verificar se usuário pode ver estatísticas da comunidade
     */
    public function viewStats(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::MODERATOR);
    }

    /**
     * Verificar se usuário pode acessar configurações avançadas
     */
    public function accessAdvancedSettings(User $user, Community $community): bool
    {
        return $community->userHasRole($user, CommunityRole::OWNER);
    }
}