<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommentVoteController extends Controller
{
    /**
     * Like ou dislike um comentário
     */
    public function vote(Request $request, Comment $comment)
    {
        $request->validate([
            'is_like' => 'required|boolean',
        ]);

        $user = Auth::user();
        $isLike = $request->boolean('is_like');

        return DB::transaction(function () use ($comment, $user, $isLike) {
            // Verificar se o usuário já votou neste comentário
            $existingVote = $comment->hasVoteFrom($user);

            if ($existingVote) {
                // Se o voto é o mesmo, remover o voto
                if ($existingVote->is_like === $isLike) {
                    $existingVote->delete();
                    $comment->updateScore();
                    
                    return response()->json([
                        'success' => true,
                        'action' => 'removed',
                        'score' => $comment->score,
                        'user_vote' => null,
                    ]);
                } else {
                    // Se é um voto diferente, atualizar o voto existente
                    $existingVote->update(['is_like' => $isLike]);
                    $comment->updateScore();
                    
                    return response()->json([
                        'success' => true,
                        'action' => 'updated',
                        'score' => $comment->score,
                        'user_vote' => $isLike ? 'like' : 'dislike',
                    ]);
                }
            } else {
                // Criar novo voto
                CommentVote::create([
                    'comment_id' => $comment->id,
                    'user_id' => $user->id,
                    'is_like' => $isLike,
                ]);
                
                $comment->updateScore();
                
                return response()->json([
                    'success' => true,
                    'action' => 'created',
                    'score' => $comment->score,
                    'user_vote' => $isLike ? 'like' : 'dislike',
                ]);
            }
        });
    }

    /**
     * Remove o voto do usuário no comentário
     */
    public function removeVote(Comment $comment)
    {
        $user = Auth::user();
        
        return DB::transaction(function () use ($comment, $user) {
            $vote = $comment->hasVoteFrom($user);
            
            if ($vote) {
                $vote->delete();
                $comment->updateScore();
                
                return response()->json([
                    'success' => true,
                    'action' => 'removed',
                    'score' => $comment->score,
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