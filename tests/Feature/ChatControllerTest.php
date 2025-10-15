<?php

use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Models\Message;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('user can view their chats', function () {
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

    $response = $this->actingAs($user)->get(route('chats.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => 
        $page->has('chats.data', 1)
            ->where('chats.data.0.id', $chat->id)
    );
});

test('user can create a private chat', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $response = $this->actingAs($user)->post(route('chats.store'), [
        'type' => 'private',
        'participant_ids' => [$otherUser->id],
    ]);

    $response->assertRedirect(route('chats.index'));
    $response->assertSessionHas('success', 'Chat criado com sucesso!');
    
    $this->assertDatabaseHas('chats', [
        'type' => 'private',
        'created_by' => $user->id,
    ]);
    
    $this->assertDatabaseHas('chat_participants', [
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
    
    $this->assertDatabaseHas('chat_participants', [
        'user_id' => $otherUser->id,
        'role' => 'member',
    ]);
});

test('user can create a group chat', function () {
    $user = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $response = $this->actingAs($user)->post(route('chats.store'), [
        'type' => 'group',
        'name' => 'Grupo de Teste',
        'description' => 'Descrição do grupo',
        'participant_ids' => [$user2->id, $user3->id],
    ]);

    $response->assertRedirect(route('chats.index'));
    $response->assertSessionHas('success', 'Chat criado com sucesso!');
    
    $this->assertDatabaseHas('chats', [
        'type' => 'group',
        'name' => 'Grupo de Teste',
        'description' => 'Descrição do grupo',
        'created_by' => $user->id,
    ]);
});

test('user cannot create private chat with multiple participants', function () {
    $user = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $response = $this->actingAs($user)->post(route('chats.store'), [
        'type' => 'private',
        'participant_ids' => [$user2->id, $user3->id],
    ]);

    $response->assertSessionHasErrors(['participant_ids']);
});

test('user can view a chat they participate in', function () {
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

    $response = $this->actingAs($user)->get(route('chats.show', $chat));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => 
        $page->where('chat.id', $chat->id)
    );
});

test('user cannot view a chat they do not participate in', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $otherUser->id,
        'role' => 'admin',
    ]);

    $response = $this->actingAs($user)->get(route('chats.show', $chat));

    $response->assertForbidden();
});

test('user can update a chat they moderate', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->group()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);

    $response = $this->actingAs($user)->patch(route('chats.update', $chat), [
        'name' => 'Novo Nome',
        'description' => 'Nova Descrição',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Chat atualizado com sucesso!');
    
    $this->assertDatabaseHas('chats', [
        'id' => $chat->id,
        'name' => 'Novo Nome',
        'description' => 'Nova Descrição',
    ]);
});

test('user cannot update a chat they do not moderate', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->group()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'member',
    ]);

    $response = $this->actingAs($user)->patch(route('chats.update', $chat), [
        'name' => 'Novo Nome',
    ]);

    $response->assertForbidden();
});

test('user can delete a chat they admin', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->group()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);

    $response = $this->actingAs($user)->delete(route('chats.destroy', $chat));

    $response->assertRedirect(route('chats.index'));
    $response->assertSessionHas('success', 'Chat deletado com sucesso!');
    
    $this->assertDatabaseMissing('chats', [
        'id' => $chat->id,
    ]);
});

test('user can archive a chat', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'member',
    ]);

    $response = $this->actingAs($user)->post(route('chats.archive', $chat));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Chat arquivado com sucesso!');
    
    $this->assertDatabaseHas('chat_participants', [
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'is_archived' => true,
    ]);
});

test('user can unarchive a chat', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'role' => 'member',
        'is_archived' => true,
    ]);

    $response = $this->actingAs($user)->post(route('chats.unarchive', $chat));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Chat desarquivado com sucesso!');
    
    $this->assertDatabaseHas('chat_participants', [
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'is_archived' => false,
    ]);
});

test('existing private chat is returned when trying to create duplicate', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    
    $existingChat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $existingChat->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
    
    ChatParticipant::factory()->create([
        'chat_id' => $existingChat->id,
        'user_id' => $otherUser->id,
        'role' => 'member',
    ]);

    $response = $this->actingAs($user)->post(route('chats.store'), [
        'type' => 'private',
        'participant_ids' => [$otherUser->id],
    ]);

    $response->assertRedirect(route('chats.show', $existingChat));
});
