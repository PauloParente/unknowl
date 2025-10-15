<?php

namespace App\Http\Controllers;

use App\Events\MessageRead as MessageReadEvent;
use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;
use App\Models\MessageRead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    /**
     * Display messages for a specific chat.
     */
    public function index(Request $request, Chat $chat): JsonResponse
    {
        $user = $request->user();
        
        // Verificar se o usuário é participante do chat
        if (!$chat->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);

        $messages = $chat->messages()
            ->with(['user:id,name,avatar', 'replyTo.user:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Transformar dados para o frontend
        $messages->getCollection()->transform(function ($message) use ($user) {
            return [
                'id' => $message->id,
                'content' => $message->content,
                'type' => $message->type,
                'metadata' => $message->metadata,
                'is_edited' => $message->is_edited,
                'edited_at' => $message->edited_at,
                'created_at' => $message->created_at,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'avatar' => $message->user->avatar,
                ],
                'reply_to' => $message->replyTo ? [
                    'id' => $message->replyTo->id,
                    'content' => $message->replyTo->content,
                    'user' => [
                        'id' => $message->replyTo->user->id,
                        'name' => $message->replyTo->user->name,
                    ],
                ] : null,
                'is_read_by_me' => $message->isReadBy($user),
            ];
        });

        return response()->json([
            'messages' => $messages,
            'chat_id' => $chat->id,
        ]);
    }

    /**
     * Store a newly created message.
     */
    public function store(Request $request, Chat $chat): JsonResponse
    {
        $user = $request->user();
        
        // Verificar se o usuário é participante do chat
        if (!$chat->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $request->validate([
            'content' => 'required|string|max:2000',
            'type' => 'in:text,image,file,system',
            'reply_to' => 'nullable|exists:messages,id',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        $message = DB::transaction(function () use ($request, $chat, $user) {
            // Criar a mensagem
            $message = Message::create([
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'content' => $request->content,
                'type' => $request->type ?? 'text',
                'reply_to' => $request->reply_to,
                'metadata' => $this->handleFileUpload($request),
            ]);

            // Atualizar timestamp da última mensagem do chat
            $chat->update(['last_message_at' => now()]);

            // Marcar mensagem como lida pelo remetente
            $message->markAsRead($user);

            return $message;
        });

        // Disparar evento para broadcast (apenas se não estiver em teste)
        if (!app()->environment('testing')) {
            broadcast(new MessageSent($message));
        }

        return response()->json([
            'success' => true,
            'message' => 'Mensagem enviada com sucesso!',
        ]);
    }

    /**
     * Update the specified message.
     */
    public function update(Request $request, Chat $chat, Message $message): JsonResponse
    {
        $user = $request->user();
        
        // Verificar se o usuário é o autor da mensagem
        if ($message->user_id !== $user->id) {
            return response()->json(['error' => 'Você só pode editar suas próprias mensagens'], 403);
        }

        // Verificar se a mensagem pode ser editada (não muito antiga)
        if ($message->created_at->diffInMinutes(now()) > 15) {
            return response()->json(['error' => 'Mensagens só podem ser editadas por 15 minutos'], 400);
        }

        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $message->edit($request->content);

        return response()->json([
            'success' => true,
            'message' => 'Mensagem editada com sucesso!',
        ]);
    }

    /**
     * Remove the specified message.
     */
    public function destroy(Chat $chat, Message $message): JsonResponse
    {
        $user = Auth::user();
        
        // Verificar se o usuário pode deletar a mensagem
        $canDelete = $message->user_id === $user->id || 
                    $message->chat->chatParticipants()
                        ->where('user_id', $user->id)
                        ->whereIn('role', ['admin', 'moderator'])
                        ->exists();

        if (!$canDelete) {
            return response()->json(['error' => 'Você não tem permissão para deletar esta mensagem'], 403);
        }

        // Deletar arquivo se existir
        if ($message->metadata && isset($message->metadata['file_path'])) {
            Storage::delete($message->metadata['file_path']);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mensagem deletada com sucesso!',
        ]);
    }

    /**
     * Mark a message as read.
     */
    public function markAsRead(Chat $chat, Message $message): JsonResponse
    {
        $user = Auth::user();
        
        // Verificar se o usuário é participante do chat
        if (!$message->chat->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $message->markAsRead($user);

        // Disparar evento para broadcast (apenas se não estiver em teste)
        if (!app()->environment('testing')) {
            broadcast(new MessageReadEvent($message, $user));
        }

        return response()->json([
            'success' => true,
            'message' => 'Mensagem marcada como lida!',
        ]);
    }

    /**
     * Mark all messages in a chat as read.
     */
    public function markAllAsRead(Chat $chat): JsonResponse
    {
        $user = Auth::user();
        
        // Verificar se o usuário é participante do chat
        if (!$chat->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $participant = $chat->chatParticipants()->where('user_id', $user->id)->first();
        if ($participant) {
            $participant->markAsRead();
        }

        // Broadcast para sincronizar badges entre abas/dispositivos
        if (!app()->environment('testing')) {
            event(new \App\Events\MessagesReadAll($chat, $user));
        }

        return response()->json([
            'success' => true,
            'message' => 'Todas as mensagens foram marcadas como lidas!',
        ]);
    }

    /**
     * Get unread message count for a chat.
     */
    public function getUnreadCount(Chat $chat): JsonResponse
    {
        $user = Auth::user();
        
        // Verificar se o usuário é participante do chat
        if (!$chat->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $unreadCount = $chat->getUnreadCount($user);

        return response()->json([
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Get unread message counts for all user's chats.
     */
    public function getUnreadCounts(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $unreadCounts = $user->chats()
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('created_at', '>', function ($subQuery) use ($user) {
                    $subQuery->select('last_read_at')
                        ->from('chat_participants')
                        ->where('chat_id', DB::raw('messages.chat_id'))
                        ->where('user_id', $user->id);
                });
            }])
            ->wherePivot('is_archived', false)
            ->get()
            ->mapWithKeys(function ($chat) {
                return [$chat->id => $chat->unread_count];
            });

        return response()->json([
            'unread_counts' => $unreadCounts,
            'total_unread' => $unreadCounts->sum(),
        ]);
    }

    /**
     * Search messages in a chat.
     */
    public function search(Request $request, Chat $chat): JsonResponse
    {
        $user = $request->user();
        
        // Verificar se o usuário é participante do chat
        if (!$chat->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $request->validate([
            'query' => 'required|string|min:2|max:100',
        ]);

        $messages = $chat->messages()
            ->with(['user:id,name,avatar'])
            ->where('content', 'like', '%' . $request->get('query') . '%')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Transformar dados para o frontend
        $messages->getCollection()->transform(function ($message) {
            return [
                'id' => $message->id,
                'content' => $message->content,
                'type' => $message->type,
                'created_at' => $message->created_at,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'avatar' => $message->user->avatar,
                ],
            ];
        });

        return response()->json([
            'messages' => $messages,
            'query' => $request->get('query'),
        ]);
    }

    /**
     * Handle file upload for messages.
     */
    private function handleFileUpload(Request $request): ?array
    {
        if (!$request->hasFile('file')) {
            return null;
        }

        $file = $request->file('file');
        $path = $file->store('chat-files', 'public');
        
        return [
            'filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }
}
