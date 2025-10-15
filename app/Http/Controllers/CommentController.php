<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\CommentEdit;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;

class CommentController extends BaseController
{
    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        $user = $request->user();

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'parent_id' => $request->input('parent_id'),
            'text' => $request->input('text'),
            'score' => 0,
        ]);

        // Atualiza contagem de comentários do post (opcional: via withCount no show)
        // Redireciona para a página do post; Inertia recarrega dados
        return redirect()
            ->route('posts.show', $post->id)
            ->with('success', 'Comentário publicado!');
    }

    public function update(UpdateCommentRequest $request, Comment $comment): RedirectResponse
    {
        $newText = $request->input('text');
        
        // Se o texto não mudou, não fazer nada
        if ($comment->text === $newText) {
            return redirect()
                ->route('posts.show', $comment->post_id)
                ->with('info', 'Nenhuma alteração detectada.');
        }

        // Salvar a versão anterior no histórico
        $versionNumber = $comment->edits()->count() + 1;
        
        CommentEdit::create([
            'comment_id' => $comment->id,
            'text' => $comment->text,
            'version_number' => $versionNumber,
        ]);

        // Atualizar o comentário com o novo texto
        $comment->update([
            'text' => $newText,
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('posts.show', $comment->post_id)
            ->with('success', 'Comentário atualizado!');
    }
}


