<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Requests\StorePostRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PostController extends BaseController
{
    public function show(Request $request, string $id): Response
    {
        $user = $request->user();
        $post = Post::with(['community', 'author', 'comments.author', 'comments.replies.author', 'votes' => function ($q) use ($user) {
                if ($user) {
                    $q->where('user_id', $user->id);
                }
            }, 'comments.votes' => function ($q) use ($user) {
                if ($user) {
                    $q->where('user_id', $user->id);
                }
            }, 'comments.replies.votes' => function ($q) use ($user) {
                if ($user) {
                    $q->where('user_id', $user->id);
                }
            }])
            ->withCount('comments')
            ->findOrFail($id);

        // Transformar dados para corresponder aos tipos do frontend
        $transformedPost = $this->transformPost($post, $user?->id);

        // Transformar comentários com replies aninhadas
        $transformedComments = $post->comments->where('parent_id', null)->map(function ($comment) use ($user) {
            return $this->transformComment($comment, $user?->id);
        })->values()->toArray();

        return Inertia::render('posts/Show', [
            'post' => $transformedPost,
            'comments' => $transformedComments,
        ]);
    }

    public function store(StorePostRequest $request)
    {
        $user = $request->user();

        $mediaPaths = [];
        $post = null;

        DB::transaction(function () use ($request, $user, &$mediaPaths, &$post) {
            // Upload de mídia (opcional, múltiplos arquivos)
            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $file) {
                    $path = $file->store('posts', ['disk' => 'public']);
                    if ($path) {
                        $mediaPaths[] = Storage::disk('public')->url($path);
                    }
                }
            }

            // Salvar todas as URLs de mídia e manter a primeira como image_url para compatibilidade
            $firstImageUrl = $mediaPaths[0] ?? null;

            $post = Post::create([
                'community_id' => (int) $request->input('community_id'),
                'user_id' => (int) $user->id,
                'title' => (string) $request->input('title'),
                'text' => $request->input('text'),
                'image_url' => $firstImageUrl,
                'media_urls' => $mediaPaths,
            ]);
        });

        return redirect()
            ->route('posts.show', $post->id)
            ->with('success', 'Post publicado com sucesso!');
    }
}

