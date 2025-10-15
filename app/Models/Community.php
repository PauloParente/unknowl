<?php

namespace App\Models;

use App\Enums\CommunityRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Community extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'title', 'description', 'avatar', 'cover_url', 'rules',
        'owner_id', 'is_public', 'requires_approval', 'member_count', 'last_activity_at',
    ];

    protected $casts = [
        'rules' => 'array',
        'is_public' => 'boolean',
        'requires_approval' => 'boolean',
        'member_count' => 'integer',
        'last_activity_at' => 'datetime',
    ];

    /**
     * Accessor para o avatar - retorna null se não houver avatar definido
     * O frontend usará iniciais como fallback
     */
    public function getAvatarAttribute($value): ?string
    {
        return $value;
    }


    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function moderators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_moderators')
            ->withPivot(['role', 'assigned_by', 'assigned_at', 'permissions', 'is_active', 'notes'])
            ->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function moderationLogs(): HasMany
    {
        return $this->hasMany(CommunityModerationLog::class);
    }

    public function bans(): HasMany
    {
        return $this->hasMany(CommunityBan::class);
    }

    public function activeBans(): HasMany
    {
        return $this->hasMany(CommunityBan::class)->active()->notExpired();
    }

    /**
     * Verifica se um usuário é o dono da comunidade
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    /**
     * Verifica se um usuário é moderador da comunidade
     */
    public function isModeratedBy(User $user): bool
    {
        return $this->moderators()->where('user_id', $user->id)->exists();
    }

    /**
     * Verifica se um usuário pode moderar a comunidade
     */
    public function canBeModeratedBy(User $user): bool
    {
        return $this->isOwnedBy($user) || $this->isModeratedBy($user);
    }

    /**
     * Verifica se um usuário é membro da comunidade
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Obter o role de um usuário na comunidade
     */
    public function getUserRole(User $user): ?CommunityRole
    {
        if ($this->isOwnedBy($user)) {
            return CommunityRole::OWNER;
        }

        $moderator = $this->moderators()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($moderator) {
            return CommunityRole::from($moderator->pivot->role);
        }

        if ($this->hasMember($user)) {
            return CommunityRole::MEMBER;
        }

        return null;
    }

    /**
     * Verificar se usuário tem um role específico ou superior
     */
    public function userHasRole(User $user, CommunityRole $requiredRole): bool
    {
        $userRole = $this->getUserRole($user);
        
        if (!$userRole) {
            return false;
        }

        return $userRole->hasHigherAuthorityThan($requiredRole) || $userRole === $requiredRole;
    }

    /**
     * Verificar se usuário pode gerenciar outro usuário
     */
    public function userCanManage(User $manager, User $target): bool
    {
        $managerRole = $this->getUserRole($manager);
        $targetRole = $this->getUserRole($target);

        if (!$managerRole || !$targetRole) {
            return false;
        }

        return $managerRole->canManage($targetRole);
    }

    /**
     * Verificar se usuário está banido da comunidade
     */
    public function isUserBanned(User $user): bool
    {
        return CommunityBan::isUserBanned($user, $this);
    }

    /**
     * Obter ban ativo de um usuário
     */
    public function getActiveBan(User $user): ?CommunityBan
    {
        return CommunityBan::getActiveBan($user, $this);
    }

    /**
     * Obter moderadores ativos por role
     */
    public function getModeratorsByRole(CommunityRole $role): \Illuminate\Database\Eloquent\Collection
    {
        return $this->moderators()
            ->wherePivot('role', $role->value)
            ->wherePivot('is_active', true)
            ->get();
    }

    /**
     * Obter todos os administradores (owner + admins)
     */
    public function getAdministrators(): \Illuminate\Database\Eloquent\Collection
    {
        $admins = $this->getModeratorsByRole(CommunityRole::ADMIN);
        $admins->prepend($this->owner);
        
        return $admins->unique('id');
    }

    /**
     * Contar moderadores por role
     */
    public function countModeratorsByRole(CommunityRole $role): int
    {
        return $this->moderators()
            ->wherePivot('role', $role->value)
            ->wherePivot('is_active', true)
            ->count();
    }

    /**
     * Verificar limites de moderadores
     */
    public function canAddModerator(CommunityRole $role): bool
    {
        return match ($role) {
            CommunityRole::ADMIN => $this->countModeratorsByRole(CommunityRole::ADMIN) < 3,
            CommunityRole::MODERATOR => $this->countModeratorsByRole(CommunityRole::MODERATOR) < 10,
            default => false,
        };
    }

    /**
     * Designar moderador
     */
    public function assignModerator(User $user, CommunityRole $role, User $assignedBy, ?array $permissions = null, ?string $notes = null): bool
    {
        if (!$this->canAddModerator($role)) {
            return false;
        }

        if (!$this->hasMember($user)) {
            return false;
        }

        return $this->moderators()->syncWithoutDetaching([
            $user->id => [
                'role' => $role->value,
                'assigned_by' => $assignedBy->id,
                'assigned_at' => now(),
                'permissions' => $permissions,
                'is_active' => true,
                'notes' => $notes,
            ]
        ]);
    }

    /**
     * Remover moderador
     */
    public function removeModerator(User $user): bool
    {
        return $this->moderators()->updateExistingPivot($user->id, [
            'is_active' => false,
        ]);
    }

    /**
     * Promover/demover moderador
     */
    public function changeModeratorRole(User $user, CommunityRole $newRole, User $changedBy): bool
    {
        if (!$this->canAddModerator($newRole)) {
            return false;
        }

        return $this->moderators()->updateExistingPivot($user->id, [
            'role' => $newRole->value,
            'assigned_by' => $changedBy->id,
            'assigned_at' => now(),
        ]);
    }
}



