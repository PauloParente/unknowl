<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'user_id',
        'role',
        'joined_at',
        'last_read_at',
        'is_muted',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'last_read_at' => 'datetime',
            'is_muted' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    // Relacionamentos
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Métodos auxiliares
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function canModerate(): bool
    {
        return $this->isAdmin() || $this->isModerator();
    }

    public function markAsRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }

    public function mute(): void
    {
        $this->update(['is_muted' => true]);
    }

    public function unmute(): void
    {
        $this->update(['is_muted' => false]);
    }

    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    public function unarchive(): void
    {
        $this->update(['is_archived' => false]);
    }

    public function getUnreadCount(): int
    {
        if (!$this->last_read_at) {
            return $this->chat->messages()->count();
        }

        return $this->chat->messages()
            ->where('created_at', '>', $this->last_read_at)
            ->count();
    }
}
