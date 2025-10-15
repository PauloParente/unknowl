<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'created_by',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    // Relacionamentos
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'desc');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_participants')
            ->withPivot(['role', 'joined_at', 'last_read_at', 'is_muted', 'is_archived'])
            ->withTimestamps();
    }

    public function chatParticipants(): HasMany
    {
        return $this->hasMany(ChatParticipant::class);
    }

    // Métodos auxiliares
    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    public function getOtherParticipant(User $user): ?User
    {
        if (!$this->isPrivate()) {
            return null;
        }

        return $this->participants()->where('user_id', '!=', $user->id)->first();
    }

    public function getDisplayName(User $user): string
    {
        if ($this->isGroup()) {
            return $this->name ?? 'Chat em Grupo';
        }

        $otherUser = $this->getOtherParticipant($user);
        return $otherUser ? $otherUser->name : 'Chat Privado';
    }

    public function getUnreadCount(User $user): int
    {
        $participant = $this->chatParticipants()->where('user_id', $user->id)->first();
        
        if (!$participant || !$participant->last_read_at) {
            return $this->messages()->count();
        }

        return $this->messages()
            ->where('created_at', '>', $participant->last_read_at)
            ->count();
    }
}
