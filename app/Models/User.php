<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'cover_url',
        'bio',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class)->withTimestamps();
    }

    public function ownedCommunities(): HasMany
    {
        return $this->hasMany(Community::class, 'owner_id');
    }

    public function moderatedCommunities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'community_moderators')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function postVotes(): HasMany
    {
        return $this->hasMany(PostVote::class);
    }

    public function commentVotes(): HasMany
    {
        return $this->hasMany(CommentVote::class);
    }

    public function moderationLogs(): HasMany
    {
        return $this->hasMany(CommunityModerationLog::class, 'moderator_id');
    }

    public function communityBans(): HasMany
    {
        return $this->hasMany(CommunityBan::class);
    }

    public function bannedCommunities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'community_bans')
            ->withPivot(['reason', 'type', 'expires_at', 'is_active', 'banned_by', 'unbanned_by', 'unbanned_at', 'unban_reason'])
            ->withTimestamps();
    }

    public function bansIssued(): HasMany
    {
        return $this->hasMany(CommunityBan::class, 'banned_by');
    }

    public function bansUnbanned(): HasMany
    {
        return $this->hasMany(CommunityBan::class, 'unbanned_by');
    }

    // Relacionamentos do sistema de chat
    public function chats(): BelongsToMany
    {
        return $this->belongsToMany(Chat::class, 'chat_participants')
            ->withPivot(['role', 'joined_at', 'last_read_at', 'is_muted', 'is_archived'])
            ->withTimestamps();
    }

    public function createdChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function chatParticipants(): HasMany
    {
        return $this->hasMany(ChatParticipant::class);
    }

    public function messageReads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    /**
     * Verificar se usuário está banido globalmente
     */
    public function isBannedGlobally(): bool
    {
        if (!$this->is_banned_globally) {
            return false;
        }

        if (!$this->banned_until) {
            return true; // Ban permanente
        }

        return $this->banned_until->isFuture();
    }

    /**
     * Verificar se usuário está mutado globalmente
     */
    public function isMutedGlobally(): bool
    {
        if (!$this->is_muted_globally) {
            return false;
        }

        if (!$this->muted_until) {
            return true; // Mute permanente
        }

        return $this->muted_until->isFuture();
    }

    /**
     * Verificar se usuário está banido de uma comunidade específica
     */
    public function isBannedFromCommunity(Community $community): bool
    {
        return $community->isUserBanned($this);
    }

    /**
     * Obter ban ativo de uma comunidade
     */
    public function getActiveBanFromCommunity(Community $community): ?CommunityBan
    {
        return $community->getActiveBan($this);
    }

    /**
     * Obter todas as comunidades onde o usuário é moderador ativo
     */
    public function getActiveModeratedCommunities()
    {
        return $this->moderatedCommunities()
            ->wherePivot('is_active', true)
            ->get();
    }

    /**
     * Obter comunidades moderadas por role
     */
    public function getModeratedCommunitiesByRole(string $role)
    {
        return $this->moderatedCommunities()
            ->wherePivot('role', $role)
            ->wherePivot('is_active', true)
            ->get();
    }

    /**
     * Verificar se usuário pode moderar uma comunidade
     */
    public function canModerateCommunity(Community $community): bool
    {
        $role = $community->getUserRole($this);
        
        return $role && in_array($role->value, ['owner', 'admin', 'moderator']);
    }

    /**
     * Accessor para o avatar - retorna null se não houver avatar definido
     * O frontend usará iniciais como fallback
     */
    public function getAvatarAttribute($value): ?string
    {
        return $value;
    }
}
