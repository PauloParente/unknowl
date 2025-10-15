<?php

use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Models\Message;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Tests\TestCase::class);

test('chat can determine if it is private', function () {
    $chat = Chat::factory()->private()->create();
    
    expect($chat->isPrivate())->toBeTrue();
    expect($chat->isGroup())->toBeFalse();
});

test('chat can determine if it is group', function () {
    $chat = Chat::factory()->group()->create();
    
    expect($chat->isGroup())->toBeTrue();
    expect($chat->isPrivate())->toBeFalse();
});

test('chat can get other participant in private chat', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user1->id,
    ]);
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user2->id,
    ]);
    
    $otherParticipant = $chat->getOtherParticipant($user1);
    
    expect($otherParticipant)->not->toBeNull();
    expect($otherParticipant->id)->toBe($user2->id);
});

test('chat returns null for other participant in group chat', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->group()->create();
    
    $otherParticipant = $chat->getOtherParticipant($user);
    
    expect($otherParticipant)->toBeNull();
});

test('chat can get display name for private chat', function () {
    $user1 = User::factory()->create(['name' => 'João']);
    $user2 = User::factory()->create(['name' => 'Maria']);
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user1->id,
    ]);
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user2->id,
    ]);
    
    $displayName = $chat->getDisplayName($user1);
    
    expect($displayName)->toBe('Maria');
});

test('chat can get display name for group chat', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->group()->create(['name' => 'Grupo de Trabalho']);
    
    $displayName = $chat->getDisplayName($user);
    
    expect($displayName)->toBe('Grupo de Trabalho');
});

test('chat can get display name for group chat without name', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->group()->create(['name' => null]);
    
    $displayName = $chat->getDisplayName($user);
    
    expect($displayName)->toBe('Chat em Grupo');
});

test('chat can calculate unread count for user', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'last_read_at' => now()->subHour(),
    ]);
    
    // Criar mensagens antigas (já lidas)
    Message::factory()->count(3)->create([
        'chat_id' => $chat->id,
        'created_at' => now()->subHours(2),
    ]);
    
    // Criar mensagens recentes (não lidas)
    Message::factory()->count(2)->create([
        'chat_id' => $chat->id,
        'created_at' => now()->subMinutes(30),
    ]);
    
    $unreadCount = $chat->getUnreadCount($user);
    
    expect($unreadCount)->toBe(2);
});

test('chat returns all messages count when user never read', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->private()->create();
    
    ChatParticipant::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'last_read_at' => null,
    ]);
    
    Message::factory()->count(5)->create([
        'chat_id' => $chat->id,
    ]);
    
    $unreadCount = $chat->getUnreadCount($user);
    
    expect($unreadCount)->toBe(5);
});

test('chat has correct relationships', function () {
    $user = User::factory()->create();
    $chat = Chat::factory()->create(['created_by' => $user->id]);
    
    // Test creator relationship
    expect($chat->creator)->not->toBeNull();
    expect($chat->creator->id)->toBe($user->id);
    
    // Test messages relationship
    Message::factory()->count(3)->create(['chat_id' => $chat->id]);
    expect($chat->messages)->toHaveCount(3);
    
    // Test participants relationship
    ChatParticipant::factory()->count(2)->create(['chat_id' => $chat->id]);
    expect($chat->participants)->toHaveCount(2);
});

test('chat can be created with factory states', function () {
    $privateChat = Chat::factory()->private()->create();
    expect($privateChat->type)->toBe('private');
    expect($privateChat->name)->toBeNull();
    
    $groupChat = Chat::factory()->group()->create();
    expect($groupChat->type)->toBe('group');
    expect($groupChat->name)->not->toBeNull();
    
    $recentChat = Chat::factory()->withRecentActivity()->create();
    expect($recentChat->last_message_at)->not->toBeNull();
    expect($recentChat->last_message_at)->toBeGreaterThan(now()->subHour());
    
    $oldChat = Chat::factory()->withOldActivity()->create();
    expect($oldChat->last_message_at)->toBeLessThan(now()->subWeek());
});
