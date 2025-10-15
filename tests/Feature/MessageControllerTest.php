<?php

use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('user can send a message to a chat they participate in', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);

    $response = $this->actingAs($user)->postJson(route('messages.store', $chat), [
        'content' => 'Olá, como você está?',
        'type' => 'text',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'Mensagem enviada com sucesso!',
    ]);
    
    $this->assertDatabaseHas('messages', [
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Olá, como você está?',
        'type' => 'text',
    ]);
});

test('user cannot send message to a chat they do not participate in', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $otherUser->id,
        'role' => 'admin',
    ]);

    $response = $this->actingAs($user)->postJson(route('messages.store', $chat), [
        'content' => 'Mensagem não autorizada',
    ]);

    $response->assertForbidden();
    $response->assertJson(['error' => 'Acesso negado']);
});

test('user can edit their own message within time limit', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
    
    $message = Message::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Mensagem original',
        'created_at' => now()->subMinutes(5), // 5 minutos atrás
    ]);

    $response = $this->actingAs($user)->patchJson(route('messages.update', [$chat, $message]), [
        'content' => 'Mensagem editada',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'Mensagem editada com sucesso!',
    ]);
    
    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'content' => 'Mensagem editada',
        'is_edited' => true,
    ]);
});

test('user cannot edit message after time limit', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
    
    $message = Message::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Mensagem original',
        'created_at' => now()->subMinutes(20), // 20 minutos atrás
    ]);

    $response = $this->actingAs($user)->patchJson(route('messages.update', [$chat, $message]), [
        'content' => 'Mensagem editada',
    ]);

    $response->assertStatus(400);
    $response->assertJson(['error' => 'Mensagens só podem ser editadas por 15 minutos']);
});

test('user cannot edit another users message', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $otherUser->id,
        'role' => 'member',
    ]);
    
    $message = Message::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $otherUser->id,
        'content' => 'Mensagem do outro usuário',
    ]);

    $response = $this->actingAs($user)->patchJson(route('messages.update', [$chat, $message]), [
        'content' => 'Tentativa de edição',
    ]);

    $response->assertForbidden();
    $response->assertJson(['error' => 'Você só pode editar suas próprias mensagens']);
});

test('user can delete their own message', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
    
    $message = Message::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Mensagem para deletar',
    ]);

    $response = $this->actingAs($user)->deleteJson(route('messages.destroy', [$chat, $message]));

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'Mensagem deletada com sucesso!',
    ]);
    
    $this->assertDatabaseMissing('messages', [
        'id' => $message->id,
    ]);
});

test('moderator can delete any message in their chat', function () {
    $moderator = User::factory()->create();
    $user = User::factory()->create();
    $chat = Chat::factory()->group()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $moderator->id,
        'role' => 'moderator',
    ]);
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'member',
    ]);
    
    $message = Message::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Mensagem do usuário',
    ]);

    $response = $this->actingAs($moderator)->deleteJson(route('messages.destroy', [$chat, $message]));

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'Mensagem deletada com sucesso!',
    ]);
    
    $this->assertDatabaseMissing('messages', [
        'id' => $message->id,
    ]);
});

test('user can mark message as read', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
    
    $message = Message::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Mensagem para marcar como lida',
    ]);

    $response = $this->actingAs($user)->postJson(route('messages.read', [$chat, $message]));

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'Mensagem marcada como lida!',
    ]);
    
    $this->assertDatabaseHas('message_reads', [
        'message_id' => $message->id,
        'user_id' => $user->id,
    ]);
});

test('user can mark all messages as read', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    $participant = ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'admin',
        'last_read_at' => now()->subHour(),
    ]);
    
    Message::factory()->count(3)->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'created_at' => now()->subMinutes(30),
    ]);

    $response = $this->actingAs($user)->postJson(route('messages.read-all', $chat));

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'Todas as mensagens foram marcadas como lidas!',
    ]);
    
    $participant->refresh();
    expect($participant->last_read_at)->toBeGreaterThan(now()->subMinutes(5));
});

test('mark all as read dispatches broadcast event', function () {
    \Illuminate\Support\Facades\Event::fake();

    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();

    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'admin',
        'last_read_at' => now()->subHour(),
    ]);

    Message::factory()->count(2)->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'created_at' => now()->subMinutes(30),
    ]);

    $this->actingAs($user)->postJson(route('messages.read-all', $chat))->assertSuccessful();

    \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\MessagesReadAll::class);
});

test('user can get unread message count', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'admin',
        'last_read_at' => now()->subHour(),
    ]);
    
    Message::factory()->count(5)->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'created_at' => now()->subMinutes(30),
    ]);

    $response = $this->actingAs($user)->getJson(route('messages.unread-count', $chat));

    $response->assertSuccessful();
    $response->assertJson([
        'unread_count' => 5,
    ]);
});

test('user can search messages in chat', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
    
    Message::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Mensagem sobre projeto importante',
    ]);
    
    Message::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Outra mensagem sem a palavra buscada',
    ]);

    $response = $this->actingAs($user)->getJson(route('messages.search', $chat) . '?query=projeto');

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'messages' => [
            'data' => [
                '*' => ['id', 'content', 'type', 'created_at', 'user']
            ]
        ],
        'query'
    ]);
    
    $responseData = $response->json();
    expect($responseData['messages']['data'])->toHaveCount(1);
    expect($responseData['messages']['data'][0]['content'])->toContain('projeto');
});

test('user can send message with reply', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
    
    $originalMessage = Message::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Mensagem original',
    ]);

    $response = $this->actingAs($user)->postJson(route('messages.store', $chat), [
        'content' => 'Resposta à mensagem',
        'type' => 'text',
        'reply_to' => $originalMessage->id,
    ]);

    $response->assertSuccessful();
    
    $this->assertDatabaseHas('messages', [
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Resposta à mensagem',
        'reply_to' => $originalMessage->id,
    ]);
});

test('user can get unread counts for all chats', function () {
    $user = User::factory()->create();
    $chat1 = Chat::factory()->private()->create();
    $chat2 = Chat::factory()->private()->create();

    // Participar dos chats
    ChatParticipant::factory()->create([
        'chat_id' => $chat1->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);

    ChatParticipant::factory()->create([
        'chat_id' => $chat2->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);

    // Criar mensagens não lidas
    Message::factory()->count(3)->create([
        'chat_id' => $chat1->id,
    ]);

    Message::factory()->count(2)->create([
        'chat_id' => $chat2->id,
    ]);

    $response = $this->actingAs($user)->getJson(route('chat.messages.unread-counts'));

    $response->assertSuccessful();
    $response->assertJson([
        'total_unread' => 5,
    ]);
    $response->assertJsonCount(2, 'unread_counts');
    $response->assertJsonPath('unread_counts.' . $chat1->id, 3);
    $response->assertJsonPath('unread_counts.' . $chat2->id, 2);
});
