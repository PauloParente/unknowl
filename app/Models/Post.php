<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id', 'user_id', 'title', 'text', 'image_url', 'media_urls', 'score', 'is_pinned', 'pinned_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
        'media_urls' => 'array',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PostVote::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostVote::class)->where('is_like', true);
    }

    public function dislikes(): HasMany
    {
        return $this->hasMany(PostVote::class)->where('is_like', false);
    }

    public function votedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_votes')
            ->withPivot('is_like')
            ->withTimestamps();
    }

    /**
     * Verifica se um usuário votou neste post
     */
    public function hasVoteFrom(User $user): ?PostVote
    {
        return $this->votes()->where('user_id', $user->id)->first();
    }

    /**
     * Calcula o score atual do post (likes - dislikes)
     */
    public function calculateScore(): int
    {
        $likes = $this->likes()->count();
        $dislikes = $this->dislikes()->count();
        return $likes - $dislikes;
    }

    /**
     * Atualiza o score do post
     */
    public function updateScore(): void
    {
        $this->update(['score' => $this->calculateScore()]);
    }
}



