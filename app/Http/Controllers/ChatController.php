<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    /**
     * Display a listing of the user's chats.
     */
    public function index(Request $request): Response|\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        
        // Log para debug
        \Log::info('ChatController.index called', [
            'user_id' => $user?->id,
            'expects_json' => $request->expectsJson(),
            'ajax' => $request->ajax(),
            'headers' => $request->headers->all()
        ]);
        
        $chats = $user->chats()
            ->with(['participants' => function ($query) use ($user) {
                $query->where('user_id', '!=', $user->id)->take(1);
            }])
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('created_at', '>', function ($subQuery) use ($user) {
                    $subQuery->select('last_read_at')
                        ->from('chat_participants')
                        ->where('chat_id', DB::raw('messages.chat_id'))
                        ->where('user_id', $user->id);
                });
            }])
            ->wherePivot('is_archived', false)
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        // Carregar última mensagem de cada chat
        $chats->getCollection()->load(['messages' => function ($query) {
            $query->latest()->limit(1);
        }]);

        // Transformar dados para o frontend
        $chats->getCollection()->transform(function ($chat) use ($user) {
            return [
                'id' => $chat->id,
                'name' => $chat->name,
                'type' => $chat->type,
                'last_message_at' => $chat->last_message_at,
                'unread_count' => $chat->unread_count,
                'last_message' => $chat->messages->first() ? [
                    'content' => $chat->messages->first()->content,
                ] : null,
                'participants' => $chat->participants->map(function ($participant) {
                    return [
                        'id' => $participant->id,
                        'name' => $participant->name,
                        'avatar' => $participant->avatar,
                    ];
                }),
            ];
        });

        // Se for requisição AJAX, retornar JSON
        if ($request->expectsJson() || $request->ajax()) {
            \Log::info('ChatController.index returning JSON', [
                'chats_count' => $chats->count()
            ]);
            return response()->json([
                'chats' => $chats,
            ]);
        }

        \Log::info('ChatController.index returning Inertia view');
        return Inertia::render('Chat/Index', [
            'chats' => $chats,
        ]);
    }

    /**
     * Display the specified chat.
     */
    public function show(Request $request, Chat $chat): Response|\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        
        // Verificar se o usuário é participante do chat
        if (!$chat->participants()->where('user_id', $user->id)->exists()) {
            abort(403, 'Você não tem acesso a este chat');
        }

        // Carregar mensagens com paginação
        $messages = $chat->messages()
            ->with(['user:id,name,avatar', 'replyTo.user:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // Carregar participantes
        $participants = $chat->chatParticipants()
            ->with('user:id,name,avatar')
            ->get()
            ->map(function ($participant) {
                return [
                    'id' => $participant->user->id,
                    'name' => $participant->user->name,
                    'avatar' => $participant->user->avatar,
                    'role' => $participant->role,
                    'joined_at' => $participant->joined_at,
                ];
            });

        // Marcar mensagens como lidas
        $this->markMessagesAsRead($chat, $user);

        // Carregar lista de chats para sidebar
        $chats = $user->chats()
            ->with(['participants' => function ($query) use ($user) {
                $query->where('user_id', '!=', $user->id)->take(1);
            }])
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('created_at', '>', function ($subQuery) use ($user) {
                    $subQuery->select('last_read_at')
                        ->from('chat_participants')
                        ->where('chat_id', DB::raw('messages.chat_id'))
                        ->where('user_id', $user->id);
                });
            }])
            ->wherePivot('is_archived', false)
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        // Carregar última mensagem de cada chat
        $chats->getCollection()->load(['messages' => function ($query) {
            $query->latest()->limit(1);
        }]);

        // Transformar dados para o frontend
        $chats->getCollection()->transform(function ($chatItem) use ($user) {
            return [
                'id' => $chatItem->id,
                'name' => $chatItem->name,
                'type' => $chatItem->type,
                'last_message_at' => $chatItem->last_message_at,
                'unread_count' => $chatItem->unread_count,
                'last_message' => $chatItem->messages->first() ? [
                    'content' => $chatItem->messages->first()->content,
                ] : null,
                'participants' => $chatItem->participants->map(function ($participant) {
                    return [
                        'id' => $participant->id,
                        'name' => $participant->name,
                        'avatar' => $participant->avatar,
                    ];
                }),
            ];
        });

        // Se for requisição AJAX, retornar JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'chat' => [
                    'id' => $chat->id,
                    'name' => $chat->name,
                    'type' => $chat->type,
                    'description' => $chat->description,
                    'created_at' => $chat->created_at,
                    'participants' => $participants,
                ],
                'messages' => $messages,
                'chats' => $chats,
            ]);
        }

        return Inertia::render('Chat/Show', [
            'chat' => [
                'id' => $chat->id,
                'name' => $chat->name,
                'type' => $chat->type,
                'description' => $chat->description,
                'created_at' => $chat->created_at,
                'participants' => $participants,
            ],
            'messages' => $messages,
            'chats' => $chats,
        ]);
    }

    /**
     * Show the form for creating a new chat.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        
        // Buscar usuários disponíveis (excluindo o próprio usuário)
        $users = User::where('id', '!=', $user->id)
            ->select('id', 'name', 'username', 'avatar')
            ->orderBy('name')
            ->get();

        return Inertia::render('Chat/Create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created chat.
     */
    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'type' => 'required|in:private,group',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
        ]);

        $user = $request->user() ?? auth()->user();
        
        // Verificar se o usuário está autenticado
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }
            return redirect()->route('login');
        }

        // Verificar se é chat privado
        if ($request->type === 'private') {
            if (count($request->participant_ids) !== 1) {
                return back()->withErrors(['participant_ids' => 'Chat privado deve ter exatamente 1 participante']);
            }

            $otherUser = User::find($request->participant_ids[0]);
            
            if (!$otherUser) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Usuário não encontrado'], 404);
                }
                return back()->withErrors(['participant_ids' => 'Usuário não encontrado']);
            }
            
            // Verificar se já existe chat privado entre estes usuários
            $existingChat = Chat::where('type', 'private')
                ->whereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->whereHas('participants', function ($query) use ($otherUser) {
                    $query->where('user_id', $otherUser->id);
                })
                ->first();

            if ($existingChat) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'chat' => [
                            'id' => $existingChat->id,
                            'type' => $existingChat->type,
                        ],
                        'message' => 'Chat existente encontrado'
                    ]);
                }
                return redirect()->route('chats.show', $existingChat);
            }
        }

        $chat = DB::transaction(function () use ($request, $user) {
            // Criar o chat
            $chat = Chat::create([
                'type' => $request->type,
                'name' => $request->name,
                'description' => $request->description,
                'created_by' => $user->id,
            ]);

            // Adicionar o criador como admin
            ChatParticipant::create([
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'joined_at' => now(),
            ]);

            // Adicionar outros participantes
            foreach ($request->participant_ids as $participantId) {
                if ($participantId !== $user->id) {
                    ChatParticipant::create([
                        'chat_id' => $chat->id,
                        'user_id' => $participantId,
                        'role' => $request->type === 'group' ? 'member' : 'member',
                        'joined_at' => now(),
                    ]);
                }
            }

            return $chat;
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'chat' => [
                    'id' => $chat->id,
                    'type' => $chat->type,
                ],
                'message' => 'Chat criado com sucesso!'
            ]);
        }

        return redirect()->route('chats.index')
            ->with('success', 'Chat criado com sucesso!');
    }

    /**
     * Search users for chat creation.
     */
    public function searchUsers(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1|max:100',
        ]);

        $user = $request->user() ?? auth()->user();
        
        // Verificar se o usuário está autenticado
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }
            return redirect()->route('login');
        }

        $query = $request->get('query');

        $users = User::where('id', '!=', $user->id)
            ->where('name', 'like', "%{$query}%")
            ->select('id', 'name', 'email', 'avatar')
            ->limit(10)
            ->get();

        return response()->json([
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->email, // Usar email como username temporariamente
                    'avatar' => $user->avatar,
                ];
            })
        ]);
    }

    /**
     * Update the specified chat.
     */
    public function update(Request $request, Chat $chat): RedirectResponse
    {
        $user = $request->user();
        
        // Verificar se o usuário pode editar o chat
        $participant = $chat->chatParticipants()->where('user_id', $user->id)->first();
        if (!$participant || !$participant->canModerate()) {
            abort(403, 'Você não tem permissão para editar este chat');
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $chat->update($request->only(['name', 'description']));

        return back()->with('success', 'Chat atualizado com sucesso!');
    }

    /**
     * Remove the specified chat.
     */
    public function destroy(Chat $chat): RedirectResponse
    {
        $user = Auth::user();
        
        // Verificar se o usuário pode deletar o chat
        $participant = $chat->chatParticipants()->where('user_id', $user->id)->first();
        if (!$participant || !$participant->isAdmin()) {
            abort(403, 'Você não tem permissão para deletar este chat');
        }

        $chat->delete();

        return redirect()->route('chats.index')
            ->with('success', 'Chat deletado com sucesso!');
    }

    /**
     * Archive the specified chat for the current user.
     */
    public function archive(Chat $chat): RedirectResponse
    {
        $user = Auth::user();
        
        $participant = $chat->chatParticipants()->where('user_id', $user->id)->first();
        if (!$participant) {
            abort(403, 'Você não é participante deste chat');
        }

        $participant->archive();

        return back()->with('success', 'Chat arquivado com sucesso!');
    }

    /**
     * Unarchive the specified chat for the current user.
     */
    public function unarchive(Chat $chat): RedirectResponse
    {
        $user = Auth::user();
        
        $participant = $chat->chatParticipants()->where('user_id', $user->id)->first();
        if (!$participant) {
            abort(403, 'Você não é participante deste chat');
        }

        $participant->unarchive();

        return back()->with('success', 'Chat desarquivado com sucesso!');
    }

    /**
     * Mark all messages in the chat as read for the current user.
     */
    private function markMessagesAsRead(Chat $chat, User $user): void
    {
        $participant = $chat->chatParticipants()->where('user_id', $user->id)->first();
        if ($participant) {
            $participant->markAsRead();
        }
    }
}
