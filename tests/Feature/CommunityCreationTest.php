<?php

use App\Models\Community;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('usuário autenticado pode acessar página de criação de comunidade', function () {
    $response = $this->get('/communities/create');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('communities/Create'));
});

test('usuário não autenticado não pode acessar página de criação', function () {
    auth()->logout();

    $response = $this->get('/communities/create');

    $response->assertRedirect('/login');
});

test('usuário pode criar uma comunidade com dados válidos', function () {
    $communityData = [
        'name' => 'test-community',
        'title' => 'Test Community',
        'description' => 'Uma comunidade de teste',
        'rules' => [
            'Seja respeitoso',
            'Sem spam',
        ],
        'is_public' => true,
        'requires_approval' => false,
    ];

    $response = $this->post('/communities', $communityData);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Comunidade criada com sucesso!');

    $this->assertDatabaseHas('communities', [
        'name' => 'test-community',
        'title' => 'Test Community',
        'owner_id' => $this->user->id,
        'is_public' => true,
        'requires_approval' => false,
    ]);

    $community = Community::where('name', 'test-community')->first();
    
    // Verificar se o usuário foi adicionado como membro
    expect($community->members)->toHaveCount(1);
    expect($community->members->first()->id)->toBe($this->user->id);
    
    // Verificar se o usuário foi adicionado como moderador owner
    expect($community->moderators)->toHaveCount(1);
    expect($community->moderators->first()->id)->toBe($this->user->id);
    expect($community->moderators->first()->pivot->role)->toBe('owner');
});

test('não é possível criar comunidade com nome duplicado', function () {
    Community::factory()->create(['name' => 'existing-community']);

    $communityData = [
        'name' => 'existing-community',
        'title' => 'Another Community',
        'description' => 'Tentativa de duplicar nome',
    ];

    $response = $this->post('/communities', $communityData);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseMissing('communities', [
        'title' => 'Another Community',
    ]);
});

test('não é possível criar comunidade com nome inválido', function () {
    $invalidNames = [
        'ab', // muito curto
        'a very long community name that exceeds the limit', // muito longo
        'Community With Caps', // maiúsculas
        'community with spaces', // espaços
        'community@with#special$chars', // caracteres especiais
    ];

    foreach ($invalidNames as $invalidName) {
        $communityData = [
            'name' => $invalidName,
            'title' => 'Test Community',
        ];

        $response = $this->post('/communities', $communityData);

        $response->assertSessionHasErrors('name');
    }
});

test('pode verificar disponibilidade do nome da comunidade', function () {
    Community::factory()->create(['name' => 'existing-community']);

    // Nome disponível
    $response = $this->get('/communities/check-name?name=available-community');
    
    $response->assertSuccessful();
    $response->assertJson([
        'available' => true,
        'message' => 'Nome disponível',
    ]);

    // Nome indisponível
    $response = $this->get('/communities/check-name?name=existing-community');
    
    $response->assertSuccessful();
    $response->assertJson([
        'available' => false,
        'message' => 'Este nome já está em uso',
    ]);
    $response->assertJsonStructure([
        'suggestions' => [],
    ]);
});

test('usuário não autenticado não pode verificar disponibilidade do nome', function () {
    auth()->logout();

    $response = $this->get('/communities/check-name?name=test-community');

    $response->assertRedirect('/login');
});

test('pode criar comunidade com upload de arquivos', function () {
    $avatar = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg', 256, 256);
    $cover = \Illuminate\Http\UploadedFile::fake()->image('cover.jpg', 1920, 384);

    $communityData = [
        'name' => 'community-with-files',
        'title' => 'Community With Files',
        'description' => 'Comunidade com arquivos',
        'avatar' => $avatar,
        'cover' => $cover,
    ];

    $response = $this->post('/communities', $communityData);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Comunidade criada com sucesso!');

    $community = Community::where('name', 'community-with-files')->first();
    
    expect($community->avatar)->not->toBeNull();
    expect($community->cover_url)->not->toBeNull();
    
    // Verificar se os arquivos foram armazenados
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($community->avatar);
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($community->cover_url);
});

test('community service pode sugerir nomes alternativos', function () {
    Community::factory()->create(['name' => 'test']);
    Community::factory()->create(['name' => 'test1']);
    Community::factory()->create(['name' => 'test2']);

    $service = app(\App\Services\CommunityService::class);
    $suggestions = $service->suggestAlternativeNames('test');

    expect($suggestions)->toBeArray();
    expect($suggestions)->not->toContain('test', 'test1', 'test2');
});

test('community service pode verificar disponibilidade do nome', function () {
    Community::factory()->create(['name' => 'existing']);

    $service = app(\App\Services\CommunityService::class);
    
    expect($service->isNameAvailable('existing'))->toBeFalse();
    expect($service->isNameAvailable('available'))->toBeTrue();
});
