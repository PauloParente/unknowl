<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Aqui é onde você pode registrar todos os canais de broadcast que sua
| aplicação suporta. Os callbacks de autorização de canal são usados para
| verificar se um usuário autenticado pode ouvir o canal.
|
*/

// Registra as rotas de autenticação de broadcasting, incluindo /broadcasting/auth
// É necessário incluir o middleware 'web' para que a sessão/cookies sejam reconhecidos
Broadcast::routes(['middleware' => ['web', 'auth', 'verified']]);

// Canal privado do chat: somente participantes do chat podem ouvir
Broadcast::channel('chat.{chatId}', function ($user, int $chatId) {
    /** @var \App\Models\User $user */
    return $user->chats()->where('chats.id', $chatId)->exists();
});


