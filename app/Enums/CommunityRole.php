<?php

namespace App\Enums;

enum CommunityRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';
    case MEMBER = 'member';

    /**
     * Obter a hierarquia de roles (maior para menor)
     */
    public static function getHierarchy(): array
    {
        return [
            self::OWNER->value => 4,
            self::ADMIN->value => 3,
            self::MODERATOR->value => 2,
            self::MEMBER->value => 1,
        ];
    }

    /**
     * Verificar se um role tem mais poder que outro
     */
    public function hasHigherAuthorityThan(CommunityRole $other): bool
    {
        $hierarchy = self::getHierarchy();
        return $hierarchy[$this->value] > $hierarchy[$other->value];
    }

    /**
     * Verificar se pode gerenciar outro role
     */
    public function canManage(CommunityRole $other): bool
    {
        // Owner pode gerenciar todos
        if ($this === self::OWNER) {
            return true;
        }

        // Admin pode gerenciar Moderator e Member
        if ($this === self::ADMIN) {
            return in_array($other, [self::MODERATOR, self::MEMBER]);
        }

        // Moderator não pode gerenciar outros roles
        return false;
    }

    /**
     * Obter roles que este role pode designar
     */
    public function canAssignRoles(): array
    {
        return match ($this) {
            self::OWNER => [self::ADMIN, self::MODERATOR],
            self::ADMIN => [self::MODERATOR],
            default => [],
        };
    }

    /**
     * Obter label amigável do role
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::OWNER => 'Dono',
            self::ADMIN => 'Administrador',
            self::MODERATOR => 'Moderador',
            self::MEMBER => 'Membro',
        };
    }

    /**
     * Obter cor do role para UI
     */
    public function getColor(): string
    {
        return match ($this) {
            self::OWNER => 'red',
            self::ADMIN => 'orange',
            self::MODERATOR => 'blue',
            self::MEMBER => 'gray',
        };
    }
}
