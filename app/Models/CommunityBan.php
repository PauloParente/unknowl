<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityBan extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id',
        'user_id',
        'banned_by',
        'reason',
        'type',
        'expires_at',
        'is_active',
        'metadata',
        'unbanned_by',
        'unbanned_at',
        'unban_reason',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'unbanned_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    public function unbannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unbanned_by');
    }

    /**
     * Scope para bans ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para bans de uma comunidade específica
     */
    public function scopeForCommunity($query, Community $community)
    {
        return $query->where('community_id', $community->id);
    }

    /**
     * Scope para bans de um usuário específico
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope para bans não expirados
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('type', 'permanent')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope para bans expirados
     */
    public function scopeExpired($query)
    {
        return $query->where('type', 'temporary')
                    ->where('expires_at', '<=', now());
    }

    /**
     * Verificar se o ban está ativo
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->type === 'permanent') {
            return true;
        }

        return $this->expires_at && $this->expires_at->isFuture();
    }

    /**
     * Verificar se o ban é permanente
     */
    public function isPermanent(): bool
    {
        return $this->type === 'permanent';
    }

    /**
     * Verificar se o ban é temporário
     */
    public function isTemporary(): bool
    {
        return $this->type === 'temporary';
    }

    /**
     * Verificar se o ban está expirado
     */
    public function isExpired(): bool
    {
        return $this->isTemporary() && $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Obter tempo restante do ban (para bans temporários)
     */
    public function getTimeRemaining(): ?string
    {
        if (!$this->isTemporary() || !$this->expires_at) {
            return null;
        }

        if ($this->isExpired()) {
            return 'Expirado';
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Desbanir usuário
     */
    public function unban(User $unbannedBy, ?string $reason = null): bool
    {
        return $this->update([
            'is_active' => false,
            'unbanned_by' => $unbannedBy->id,
            'unbanned_at' => now(),
            'unban_reason' => $reason,
        ]);
    }

    /**
     * Criar um novo ban
     */
    public static function createBan(
        Community $community,
        User $user,
        User $bannedBy,
        string $type = 'permanent',
        ?string $reason = null,
        ?\DateTimeInterface $expiresAt = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'community_id' => $community->id,
            'user_id' => $user->id,
            'banned_by' => $bannedBy->id,
            'reason' => $reason,
            'type' => $type,
            'expires_at' => $expiresAt,
            'is_active' => true,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Verificar se um usuário está banido de uma comunidade
     */
    public static function isUserBanned(User $user, Community $community): bool
    {
        return self::active()
            ->forCommunity($community)
            ->forUser($user)
            ->notExpired()
            ->exists();
    }

    /**
     * Obter ban ativo de um usuário em uma comunidade
     */
    public static function getActiveBan(User $user, Community $community): ?self
    {
        return self::active()
            ->forCommunity($community)
            ->forUser($user)
            ->notExpired()
            ->first();
    }
}