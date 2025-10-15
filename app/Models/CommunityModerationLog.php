<?php

namespace App\Models;

use App\Enums\ModerationAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommunityModerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id',
        'moderator_id',
        'target_user_id',
        'action',
        'target_type',
        'target_id',
        'reason',
        'metadata',
        'previous_data',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'previous_data' => 'array',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Relacionamento polimórfico para o target da ação
     */
    public function target(): MorphTo
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    /**
     * Scope para logs de uma comunidade específica
     */
    public function scopeForCommunity($query, Community $community)
    {
        return $query->where('community_id', $community->id);
    }

    /**
     * Scope para logs de um moderador específico
     */
    public function scopeByModerator($query, User $moderator)
    {
        return $query->where('moderator_id', $moderator->id);
    }

    /**
     * Scope para logs de uma ação específica
     */
    public function scopeForAction($query, ModerationAction $action)
    {
        return $query->where('action', $action->value);
    }

    /**
     * Scope para logs ativos (não expirados)
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope para logs de um usuário específico
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('target_user_id', $user->id);
    }

    /**
     * Verificar se o log está ativo
     */
    public function isActive(): bool
    {
        return is_null($this->expires_at) || $this->expires_at->isFuture();
    }

    /**
     * Verificar se o log está expirado
     */
    public function isExpired(): bool
    {
        return !is_null($this->expires_at) && $this->expires_at->isPast();
    }

    /**
     * Obter a ação como enum
     */
    public function getActionEnum(): ModerationAction
    {
        return ModerationAction::from($this->action);
    }

    /**
     * Obter o label da ação
     */
    public function getActionLabel(): string
    {
        return $this->getActionEnum()->getLabel();
    }

    /**
     * Obter a cor da ação
     */
    public function getActionColor(): string
    {
        return $this->getActionEnum()->getColor();
    }

    /**
     * Criar um novo log de moderação
     */
    public static function createLog(
        Community $community,
        User $moderator,
        ModerationAction $action,
        ?User $targetUser = null,
        ?string $reason = null,
        ?array $metadata = null,
        ?array $previousData = null,
        ?\DateTimeInterface $expiresAt = null
    ): self {
        return self::create([
            'community_id' => $community->id,
            'moderator_id' => $moderator->id,
            'target_user_id' => $targetUser?->id,
            'action' => $action->value,
            'target_type' => $action->getTargetType(),
            'target_id' => $metadata['target_id'] ?? null,
            'reason' => $reason,
            'metadata' => $metadata,
            'previous_data' => $previousData,
            'expires_at' => $expiresAt,
            'status' => 'completed',
        ]);
    }
}