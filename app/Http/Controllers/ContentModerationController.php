<?php

namespace App\Http\Controllers;

use App\Enums\ModerationAction;
use App\Models\Community;
use App\Models\CommunityModerationLog;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContentModerationController extends Controller
{
    /**
     * Fixar/Desfixar post
     */
    public function togglePinPost(Request $request, Community $community, Post $post): JsonResponse
    {
        $this->authorize('pinPosts', $community);

        $isPinned = $post->is_pinned;
        $action = $isPinned ? ModerationAction::UNPIN_POST : ModerationAction::PIN_POST;

        DB::transaction(function () use ($post, $action, $community, $request) {
            // Alterar status do pin
            $post->update(['is_pinned' => !$post->is_pinned]);

            // Log da ação
            CommunityModerationLog::createLog(
                $community,
                Auth::user(),
                $action,
                $post->user,
                $request->get('reason'),
                ['post_id' => $post->id]
            );
        });

        $message = $isPinned ? 'Post desfixado com sucesso' : 'Post fixado com sucesso';

        return response()->json([
            'message' => $message,
            'is_pinned' => !$isPinned,
        ]);
    }

    /**
     * Bloquear/Desbloquear post
     */
    public function toggleLockPost(Request $request, Community $community, Post $post): JsonResponse
    {
        $this->authorize('lockPosts', $community);

        $isLocked = $post->is_locked ?? false;
        $action = $isLocked ? ModerationAction::UNLOCK_POST : ModerationAction::LOCK_POST;

        DB::transaction(function () use ($post, $action, $community, $request) {
            // Alterar status do lock
            $post->update(['is_locked' => !$isLocked]);

            // Log da ação
            CommunityModerationLog::createLog(
                $community,
                Auth::user(),
                $action,
                $post->user,
                $request->get('reason'),
                ['post_id' => $post->id]
            );
        });

        $message = $isLocked ? 'Post desbloqueado com sucesso' : 'Post bloqueado com sucesso';

        return response()->json([
            'message' => $message,
            'is_locked' => !$isLocked,
        ]);
    }

    /**
     * Remover post
     */
    public function removePost(Request $request, Community $community, Post $post): JsonResponse
    {
        $this->authorize('moderateContent', $community);

        // Verificar se pode remover este post
        if (!$community->userCanManage(Auth::user(), $post->user) && $post->user_id !== Auth::id()) {
            return response()->json(['message' => 'Você não pode remover este post'], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($post, $community, $request) {
            // Marcar como removido (soft delete)
            $post->update([
                'deleted_at' => now(),
                'deleted_by' => Auth::id(),
                'deletion_reason' => $request->reason,
            ]);

            // Log da ação
            CommunityModerationLog::createLog(
                $community,
                Auth::user(),
                ModerationAction::REMOVE_POST,
                $post->user,
                $request->reason,
                ['post_id' => $post->id]
            );
        });

        return response()->json(['message' => 'Post removido com sucesso']);
    }

    /**
     * Restaurar post
     */
    public function restorePost(Request $request, Community $community, Post $post): JsonResponse
    {
        $this->authorize('moderateContent', $community);

        if (!$post->trashed()) {
            return response()->json(['message' => 'Post não está removido'], 400);
        }

        DB::transaction(function () use ($post, $community, $request) {
            // Restaurar post
            $post->update([
                'deleted_at' => null,
                'deleted_by' => null,
                'deletion_reason' => null,
            ]);

            // Log da ação
            CommunityModerationLog::createLog(
                $community,
                Auth::user(),
                ModerationAction::RESTORE_POST,
                $post->user,
                $request->get('reason'),
                ['post_id' => $post->id]
            );
        });

        return response()->json(['message' => 'Post restaurado com sucesso']);
    }

    /**
     * Remover comentário
     */
    public function removeComment(Request $request, Community $community, Comment $comment): JsonResponse
    {
        $this->authorize('moderateContent', $community);

        // Verificar se pode remover este comentário
        if (!$community->userCanManage(Auth::user(), $comment->user) && $comment->user_id !== Auth::id()) {
            return response()->json(['message' => 'Você não pode remover este comentário'], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($comment, $community, $request) {
            // Marcar como removido (soft delete)
            $comment->update([
                'deleted_at' => now(),
                'deleted_by' => Auth::id(),
                'deletion_reason' => $request->reason,
            ]);

            // Log da ação
            CommunityModerationLog::createLog(
                $community,
                Auth::user(),
                ModerationAction::REMOVE_COMMENT,
                $comment->user,
                $request->reason,
                ['comment_id' => $comment->id]
            );
        });

        return response()->json(['message' => 'Comentário removido com sucesso']);
    }

    /**
     * Restaurar comentário
     */
    public function restoreComment(Request $request, Community $community, Comment $comment): JsonResponse
    {
        $this->authorize('moderateContent', $community);

        if (!$comment->trashed()) {
            return response()->json(['message' => 'Comentário não está removido'], 400);
        }

        DB::transaction(function () use ($comment, $community, $request) {
            // Restaurar comentário
            $comment->update([
                'deleted_at' => null,
                'deleted_by' => null,
                'deletion_reason' => null,
            ]);

            // Log da ação
            CommunityModerationLog::createLog(
                $community,
                Auth::user(),
                ModerationAction::RESTORE_COMMENT,
                $comment->user,
                $request->get('reason'),
                ['comment_id' => $comment->id]
            );
        });

        return response()->json(['message' => 'Comentário restaurado com sucesso']);
    }
}