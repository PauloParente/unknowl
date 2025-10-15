<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostVoteController extends Controller
{
    /**
     * Like ou dislike um post
     */
    public function vote(Request $request, Post $post)
    {
        \Log::info('PostVoteController::vote executado', [
            'user_id' => Auth::id(),
            'post_id' => $post->id,
            'request_data' => $request->all(),
        ]);
        
        $request->validate([
            'is_like' => 'required|boolean',
        ]);

        $user = Auth::user();
        $isLike = $request->boolean('is_like');

        return DB::transaction(function () use ($post, $user, $isLike) {
            // Verificar se o usuário já votou neste post
            $existingVote = $post->hasVoteFrom($user);

            if ($existingVote) {
                // Se o voto é o mesmo, remover o voto
                if ($existingVote->is_like === $isLike) {
                    $existingVote->delete();
                    $post->updateScore();
                    
                    return response()->json([
                        'success' => true,
                        'action' => 'removed',
                        'score' => $post->score,
                        'user_vote' => null,
                    ]);
                } else {
                    // Se é um voto diferente, atualizar o voto existente
                    $existingVote->update(['is_like' => $isLike]);
                    $post->updateScore();
                    
                    return response()->json([
                        'success' => true,
                        'action' => 'updated',
                        'score' => $post->score,
                        'user_vote' => $isLike ? 'like' : 'dislike',
                    ]);
                }
            } else {
                // Criar novo voto
                PostVote::create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'is_like' => $isLike,
                ]);
                
                $post->updateScore();
                
                return response()->json([
                    'success' => true,
                    'action' => 'created',
                    'score' => $post->score,
                    'user_vote' => $isLike ? 'like' : 'dislike',
                ]);
            }
        });
    }

    /**
     * Remove o voto do usuário no post
     */
    public function removeVote(Post $post)
    {
        $user = Auth::user();
        
        return DB::transaction(function () use ($post, $user) {
            $vote = $post->hasVoteFrom($user);
            
            if ($vote) {
                $vote->delete();
                $post->updateScore();
                
                return response()->json([
                    'success' => true,
                    'action' => 'removed',
                    'score' => $post->score,
                    'user_vote' => null,
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Voto não encontrado',
            ], 404);
        });
    }
}