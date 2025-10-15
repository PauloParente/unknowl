<?php

namespace App\Enums;

enum ModerationAction: string
{
    // Ações de usuário
    case BAN_USER = 'ban_user';
    case UNBAN_USER = 'unban_user';
    case WARN_USER = 'warn_user';
    case MUTE_USER = 'mute_user';
    case UNMUTE_USER = 'unmute_user';
    
    // Ações de post
    case REMOVE_POST = 'remove_post';
    case RESTORE_POST = 'restore_post';
    case PIN_POST = 'pin_post';
    case UNPIN_POST = 'unpin_post';
    case LOCK_POST = 'lock_post';
    case UNLOCK_POST = 'unlock_post';
    
    // Ações de comentário
    case REMOVE_COMMENT = 'remove_comment';
    case RESTORE_COMMENT = 'restore_comment';
    
    // Ações de moderação
    case ASSIGN_MODERATOR = 'assign_moderator';
    case REMOVE_MODERATOR = 'remove_moderator';
    case PROMOTE_MODERATOR = 'promote_moderator';
    case DEMOTE_MODERATOR = 'demote_moderator';
    
    // Ações de comunidade
    case UPDATE_SETTINGS = 'update_settings';
    case UPDATE_RULES = 'update_rules';
    case TRANSFER_OWNERSHIP = 'transfer_ownership';
    
    // Ações de membro
    case APPROVE_MEMBER = 'approve_member';
    case REJECT_MEMBER = 'reject_member';
    case REMOVE_MEMBER = 'remove_member';

    /**
     * Obter o label amigável da ação
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::BAN_USER => 'Banir Usuário',
            self::UNBAN_USER => 'Desbanir Usuário',
            self::WARN_USER => 'Avisar Usuário',
            self::MUTE_USER => 'Silenciar Usuário',
            self::UNMUTE_USER => 'Remover Silêncio',
            self::REMOVE_POST => 'Remover Post',
            self::RESTORE_POST => 'Restaurar Post',
            self::PIN_POST => 'Fixar Post',
            self::UNPIN_POST => 'Desfixar Post',
            self::LOCK_POST => 'Bloquear Post',
            self::UNLOCK_POST => 'Desbloquear Post',
            self::REMOVE_COMMENT => 'Remover Comentário',
            self::RESTORE_COMMENT => 'Restaurar Comentário',
            self::ASSIGN_MODERATOR => 'Designar Moderador',
            self::REMOVE_MODERATOR => 'Remover Moderador',
            self::PROMOTE_MODERATOR => 'Promover Moderador',
            self::DEMOTE_MODERATOR => 'Rebaixar Moderador',
            self::UPDATE_SETTINGS => 'Atualizar Configurações',
            self::UPDATE_RULES => 'Atualizar Regras',
            self::TRANSFER_OWNERSHIP => 'Transferir Propriedade',
            self::APPROVE_MEMBER => 'Aprovar Membro',
            self::REJECT_MEMBER => 'Rejeitar Membro',
            self::REMOVE_MEMBER => 'Remover Membro',
        };
    }

    /**
     * Obter a cor da ação para UI
     */
    public function getColor(): string
    {
        return match ($this) {
            self::BAN_USER, self::REMOVE_POST, self::REMOVE_COMMENT, self::REMOVE_MEMBER => 'red',
            self::UNBAN_USER, self::RESTORE_POST, self::RESTORE_COMMENT => 'green',
            self::PIN_POST, self::ASSIGN_MODERATOR, self::PROMOTE_MODERATOR, self::APPROVE_MEMBER => 'blue',
            self::UNPIN_POST, self::REMOVE_MODERATOR, self::DEMOTE_MODERATOR, self::REJECT_MEMBER => 'orange',
            self::LOCK_POST, self::MUTE_USER => 'yellow',
            self::UNLOCK_POST, self::UNMUTE_USER => 'green',
            self::WARN_USER => 'amber',
            self::UPDATE_SETTINGS, self::UPDATE_RULES => 'purple',
            self::TRANSFER_OWNERSHIP => 'pink',
        };
    }

    /**
     * Verificar se a ação requer confirmação especial
     */
    public function requiresConfirmation(): bool
    {
        return in_array($this, [
            self::BAN_USER,
            self::REMOVE_MODERATOR,
            self::DEMOTE_MODERATOR,
            self::TRANSFER_OWNERSHIP,
            self::REMOVE_MEMBER,
        ]);
    }

    /**
     * Obter o tipo de target da ação
     */
    public function getTargetType(): ?string
    {
        return match ($this) {
            self::BAN_USER, self::UNBAN_USER, self::WARN_USER, self::MUTE_USER, self::UNMUTE_USER,
            self::ASSIGN_MODERATOR, self::REMOVE_MODERATOR, self::PROMOTE_MODERATOR, self::DEMOTE_MODERATOR,
            self::APPROVE_MEMBER, self::REJECT_MEMBER, self::REMOVE_MEMBER => 'user',
            
            self::REMOVE_POST, self::RESTORE_POST, self::PIN_POST, self::UNPIN_POST, 
            self::LOCK_POST, self::UNLOCK_POST => 'post',
            
            self::REMOVE_COMMENT, self::RESTORE_COMMENT => 'comment',
            
            self::UPDATE_SETTINGS, self::UPDATE_RULES, self::TRANSFER_OWNERSHIP => 'community',
            
            default => null,
        };
    }
}
