<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'user_id',
        'content',
        'type',
        'metadata',
        'reply_to',
        'is_edited',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_edited' => 'boolean',
            'edited_at' => 'datetime',
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

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    // Métodos auxiliares
    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    public function isSystem(): bool
    {
        return $this->type === 'system';
    }

    public function markAsRead(User $user): void
    {
        $this->reads()->updateOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()]
        );
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function getReadBy(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->reads()->with('user')->get()->pluck('user');
    }

    public function edit(string $newContent): void
    {
        $this->update([
            'content' => $newContent,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }
}
