<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id', 'user_id', 'parent_id', 'text', 'original_text', 'score',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CommentVote::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentVote::class)->where('is_like', true);
    }

    public function dislikes(): HasMany
    {
        return $this->hasMany(CommentVote::class)->where('is_like', false);
    }

    public function votedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'comment_votes')
            ->withPivot('is_like')
            ->withTimestamps();
    }

    public function edits(): HasMany
    {
        return $this->hasMany(CommentEdit::class)->orderBy('version_number', 'desc');
    }

    /**
     * Verifica se um usuário votou neste comentário
     */
    public function hasVoteFrom(User $user): ?CommentVote
    {
        return $this->votes()->where('user_id', $user->id)->first();
    }

    /**
     * Calcula o score atual do comentário (likes - dislikes)
     */
    public function calculateScore(): int
    {
        $likes = $this->likes()->count();
        $dislikes = $this->dislikes()->count();
        return $likes - $dislikes;
    }

    /**
     * Atualiza o score do comentário
     */
    public function updateScore(): void
    {
        $this->update(['score' => $this->calculateScore()]);
    }
}



