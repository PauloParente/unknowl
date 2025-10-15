<?php

namespace App\Policies;

use App\Enums\CommunityRole;
use App\Enums\ModerationAction;
use App\Models\Community;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ModerationPolicy
{
    use HandlesAuthorization;

    /**
     * Verificar se usuário pode executar uma ação de moderação específica
     */
    public function performAction(User $user, Community $community, ModerationAction $action): bool
    {
        $userRole = $community->getUserRole($user);
        
        if (!$userRole || $userRole === CommunityRole::MEMBER) {
            return false;
        }

        return match ($action) {
            // Ações que apenas ADMIN pode fazer
            ModerationAction::ASSIGN_MODERATOR,
            ModerationAction::REMOVE_MODERATOR,
            ModerationAction::PROMOTE_MODERATOR,
            ModerationAction::DEMOTE_MODERATOR,
            ModerationAction::UPDATE_SETTINGS,
            ModerationAction::UPDATE_RULES,
            ModerationAction::TRANSFER_OWNERSHIP,
            ModerationAction::PIN_POST,
            ModerationAction::UNPIN_POST => $userRole->hasHigherAuthorityThan(CommunityRole::MODERATOR),

            // Ações que MODERATOR pode fazer
            ModerationAction::BAN_USER,
            ModerationAction::UNBAN_USER,
            ModerationAction::WARN_USER,
            ModerationAction::MUTE_USER,
            ModerationAction::UNMUTE_USER,
            ModerationAction::REMOVE_POST,
            ModerationAction::RESTORE_POST,
            ModerationAction::LOCK_POST,
            ModerationAction::UNLOCK_POST,
            ModerationAction::REMOVE_COMMENT,
            ModerationAction::RESTORE_COMMENT,
            ModerationAction::APPROVE_MEMBER,
            ModerationAction::REJECT_MEMBER,
            ModerationAction::REMOVE_MEMBER => $userRole->hasHigherAuthorityThan(CommunityRole::MEMBER),

            default => false,
        };
    }

    /**
     * Verificar se usuário pode banir outro usuário específico
     */
    public function banUser(User $user, Community $community, User $targetUser): bool
    {
        // Verificar permissão básica
        if (!$this->performAction($user, $community, ModerationAction::BAN_USER)) {
            return false;
        }

        // Não pode banir a si mesmo
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Verificar se pode gerenciar o usuário alvo
        return $community->userCanManage($user, $targetUser);
    }

    /**
     * Verificar se usuário pode desbanir outro usuário específico
     */
    public function unbanUser(User $user, Community $community, User $targetUser): bool
    {
        // Verificar permissão básica
        if (!$this->performAction($user, $community, ModerationAction::UNBAN_USER)) {
            return false;
        }

        // Verificar se pode gerenciar o usuário alvo
        return $community->userCanManage($user, $targetUser);
    }

    /**
     * Verificar se usuário pode remover um post específico
     */
    public function removePost(User $user, Community $community, Post $post): bool
    {
        // Verificar permissão básica
        if (!$this->performAction($user, $community, ModerationAction::REMOVE_POST)) {
            return false;
        }

        // Verificar se o post pertence à comunidade
        if ($post->community_id !== $community->id) {
            return false;
        }

        // Se o post é do próprio usuário, verificar se pode gerenciar
        if ($post->user_id === $user->id) {
            return true; // Pode remover próprio post
        }

        // Para posts de outros usuários, verificar se pode gerenciar o autor
        return $community->userCanManage($user, $post->user);
    }

    /**
     * Verificar se usuário pode remover um comentário específico
     */
    public function removeComment(User $user, Community $community, Comment $comment): bool
    {
        // Verificar permissão básica
        if (!$this->performAction($user, $community, ModerationAction::REMOVE_COMMENT)) {
            return false;
        }

        // Verificar se o comentário pertence à comunidade
        if ($comment->post->community_id !== $community->id) {
            return false;
        }

        // Se o comentário é do próprio usuário, verificar se pode gerenciar
        if ($comment->user_id === $user->id) {
            return true; // Pode remover próprio comentário
        }

        // Para comentários de outros usuários, verificar se pode gerenciar o autor
        return $community->userCanManage($user, $comment->user);
    }

    /**
     * Verificar se usuário pode fixar um post específico
     */
    public function pinPost(User $user, Community $community, Post $post): bool
    {
        // Verificar permissão básica
        if (!$this->performAction($user, $community, ModerationAction::PIN_POST)) {
            return false;
        }

        // Verificar se o post pertence à comunidade
        return $post->community_id === $community->id;
    }

    /**
     * Verificar se usuário pode bloquear um post específico
     */
    public function lockPost(User $user, Community $community, Post $post): bool
    {
        // Verificar permissão básica
        if (!$this->performAction($user, $community, ModerationAction::LOCK_POST)) {
            return false;
        }

        // Verificar se o post pertence à comunidade
        return $post->community_id === $community->id;
    }

    /**
     * Verificar se usuário pode designar um moderador específico
     */
    public function assignModerator(User $user, Community $community, User $targetUser, string $role): bool
    {
        // Verificar permissão básica
        if (!$this->performAction($user, $community, ModerationAction::ASSIGN_MODERATOR)) {
            return false;
        }

        // Não pode designar a si mesmo
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Verificar se pode gerenciar o usuário alvo
        if (!$community->userCanManage($user, $targetUser)) {
            return false;
        }

        // Verificar se a comunidade pode adicionar mais moderadores deste role
        $roleEnum = CommunityRole::from($role);
        return $community->canAddModerator($roleEnum);
    }

    /**
     * Verificar se usuário pode remover um moderador específico
     */
    public function removeModerator(User $user, Community $community, User $targetModerator): bool
    {
        // Verificar permissão básica
        if (!$this->performAction($user, $community, ModerationAction::REMOVE_MODERATOR)) {
            return false;
        }

        // Não pode remover a si mesmo
        if ($user->id === $targetModerator->id) {
            return false;
        }

        // Verificar se pode gerenciar o moderador alvo
        return $community->userCanManage($user, $targetModerator);
    }

    /**
     * Verificar se usuário pode aprovar um membro específico
     */
    public function approveMember(User $user, Community $community, User $targetUser): bool
    {
        // Verificar permissão básica
        if (!$this->performAction($user, $community, ModerationAction::APPROVE_MEMBER)) {
            return false;
        }

        // Verificar se pode gerenciar o usuário alvo
        return $community->userCanManage($user, $targetUser);
    }

    /**
     * Verificar se usuário pode remover um membro específico
     */
    public function removeMember(User $user, Community $community, User $targetUser): bool
    {
        // Verificar permissão básica
        if (!$this->performAction($user, $community, ModerationAction::REMOVE_MEMBER)) {
            return false;
        }

        // Não pode remover a si mesmo
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Verificar se pode gerenciar o usuário alvo
        return $community->userCanManage($user, $targetUser);
    }

    /**
     * Verificar se usuário pode transferir propriedade para um usuário específico
     */
    public function transferOwnership(User $user, Community $community, User $targetUser): bool
    {
        // Verificar permissão básica
        if (!$this->performAction($user, $community, ModerationAction::TRANSFER_OWNERSHIP)) {
            return false;
        }

        // Verificar se o usuário alvo é admin da comunidade
        $targetRole = $community->getUserRole($targetUser);
        return $targetRole === CommunityRole::ADMIN;
    }

    /**
     * Verificar se usuário pode ver logs de moderação
     */
    public function viewLogs(User $user, Community $community): bool
    {
        $userRole = $community->getUserRole($user);
        return $userRole && $userRole->hasHigherAuthorityThan(CommunityRole::MEMBER);
    }

    /**
     * Verificar se usuário pode ver logs de um moderador específico
     */
    public function viewModeratorLogs(User $user, Community $community, User $targetModerator): bool
    {
        // Verificar permissão básica para ver logs
        if (!$this->viewLogs($user, $community)) {
            return false;
        }

        $userRole = $community->getUserRole($user);
        $targetRole = $community->getUserRole($targetModerator);

        // Owner e Admin podem ver logs de qualquer moderador
        if ($userRole->hasHigherAuthorityThan(CommunityRole::MODERATOR)) {
            return true;
        }

        // Moderador só pode ver seus próprios logs
        return $user->id === $targetModerator->id;
    }
}