<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('botão criar comunidade está habilitado quando nome está disponível', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/communities/create');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('communities/Create'));
});

test('botão criar comunidade está desabilitado quando nome não está disponível', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Criar uma comunidade existente
    \App\Models\Community::factory()->create(['name' => 'existing-community']);

    $response = $this->get('/communities/create');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('communities/Create'));
});

test('botão criar comunidade funciona corretamente com dados válidos', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $communityData = [
        'name' => 'test-community-button',
        'title' => 'Test Community Button',
        'description' => 'Teste do botão',
        'is_public' => true,
        'requires_approval' => false,
    ];

    $response = $this->post('/communities', $communityData);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Comunidade criada com sucesso!');

    $this->assertDatabaseHas('communities', [
        'name' => 'test-community-button',
        'title' => 'Test Community Button',
        'owner_id' => $user->id,
    ]);
});