<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class BaseController extends Controller
{
    use AuthorizesRequests, HandlesAuthorization;
    /**
     * Transforma um post para o formato do frontend incluindo informações de voto
     */
    protected function transformPost(Post $post, ?int $userId = null): array
    {
        $userVote = null;
        if ($userId && $post->relationLoaded('votes')) {
            $vote = $post->votes->where('user_id', $userId)->first();
            if ($vote) {
                $userVote = $vote->is_like ? 'like' : 'dislike';
            }
        }

        return [
            'id' => $post->id,
            'community' => [
                'id' => $post->community->id,
                'name' => $post->community->name,
                'title' => $post->community->title,
                'avatar' => $post->community->avatar,
                'cover_url' => $post->community->cover_url,
                'description' => $post->community->description,
                'created_at' => $post->community->created_at->toISOString(),
                'members_count' => $post->community->member_count ?? 0,
                'online_count' => 0, // TODO: Implementar contagem de usuários online
                'posts_count' => $post->community->posts()->count(),
            ],
            'author' => [
                'id' => $post->author->id,
                'name' => $post->author->name,
                'avatar' => $post->author->avatar,
            ],
            'title' => $post->title,
            'text' => $post->text,
            'imageUrl' => $post->image_url,
            'mediaUrls' => $post->media_urls ?? [],
            'created_at' => $post->created_at->toISOString(),
            'score' => $post->score,
            'comments_count' => $post->comments_count,
            'is_pinned' => $post->is_pinned,
            'pinned_at' => $post->pinned_at?->toISOString(),
            'user_vote' => $userVote,
        ];
    }

    /**
     * Transforma um comentário para o formato do frontend incluindo informações de voto
     */
    protected function transformComment(Comment $comment, ?int $userId = null): array
    {
        $userVote = null;
        if ($userId && $comment->relationLoaded('votes')) {
            $vote = $comment->votes->where('user_id', $userId)->first();
            if ($vote) {
                $userVote = $vote->is_like ? 'like' : 'dislike';
            }
        }

        // Preparar histórico de edições
        $editHistory = $comment->edits->map(function ($edit) {
            return [
                'id' => $edit->id,
                'text' => $edit->text,
                'version_number' => $edit->version_number,
                'created_at' => $edit->created_at->toISOString(),
            ];
        })->toArray();

        $transformed = [
            'id' => $comment->id,
            'author' => [
                'id' => $comment->author->id,
                'name' => $comment->author->name,
                'avatar' => $comment->author->avatar,
            ],
            'text' => $comment->text,
            'original_text' => $comment->original_text,
            'edit_history' => $editHistory,
            'created_at' => $comment->created_at->toISOString(),
            'updated_at' => $comment->updated_at->toISOString(),
            'score' => $comment->score,
            'user_vote' => $userVote,
        ];

        // Transformar replies recursivamente
        if ($comment->replies && $comment->replies->count() > 0) {
            $transformed['replies'] = $comment->replies->map(function ($reply) use ($userId) {
                return $this->transformComment($reply, $userId);
            })->toArray();
        }

        return $transformed;
    }
}
