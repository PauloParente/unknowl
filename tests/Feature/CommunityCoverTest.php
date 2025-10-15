<?php

use App\Models\User;
use App\Models\Community;

it('creates new community with default cover placeholder', function () {
    $user = User::factory()->create();
    
    $community = Community::factory()->create([
        'name' => 'technology',
        'cover_url' => null,
        'owner_id' => $user->id
    ]);

    // Verificar se o accessor retorna uma capa padrão
    expect($community->cover_url)->toStartWith('/images/placeholders/community-cover-');
    expect($community->cover_url)->toEndWith('.svg');
});

it('displays community cover correctly when user is owner', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create([
        'name' => 'programming',
        'cover_url' => null,
        'owner_id' => $user->id
    ]);

    $response = $this->actingAs($user)
        ->get("/r/{$community->name}");

    $response->assertOk();
    
    // Verificar se a página carrega sem erros
    $response->assertInertia(fn ($page) => 
        $page->component('communities/Show')
            ->has('community')
            ->where('community.name', 'r/' . $community->name)
            ->where('community.cover_url', $community->cover_url)
    );
});

it('displays community cover correctly when user is not owner', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    
    $community = Community::factory()->create([
        'name' => 'web-development',
        'cover_url' => null,
        'owner_id' => $owner->id
    ]);

    $response = $this->actingAs($visitor)
        ->get("/r/{$community->name}");

    $response->assertOk();
    
    // Verificar se a página carrega sem erros
    $response->assertInertia(fn ($page) => 
        $page->component('communities/Show')
            ->has('community')
            ->where('community.name', 'r/' . $community->name)
            ->where('community.cover_url', $community->cover_url)
    );
});

it('uses consistent cover color for same community name', function () {
    $user = User::factory()->create();
    
    $community = Community::factory()->create([
        'name' => 'technology',
        'cover_url' => null,
        'owner_id' => $user->id
    ]);

    // Recarregar a comunidade para testar o accessor
    $community->refresh();

    // Verificar se a comunidade tem uma cor de capa consistente
    expect($community->cover_url)->toStartWith('/images/placeholders/community-cover-');
    expect($community->cover_url)->toEndWith('.svg');
    
    // Verificar se a cor é consistente entre múltiplas chamadas
    $coverUrl1 = $community->cover_url;
    $coverUrl2 = $community->cover_url;
    expect($coverUrl1)->toBe($coverUrl2);
});

it('uses different cover colors for different community names', function () {
    $user = User::factory()->create();
    
    $community1 = Community::factory()->create([
        'name' => 'technology',
        'cover_url' => null,
        'owner_id' => $user->id
    ]);
    
    $community2 = Community::factory()->create([
        'name' => 'programming',
        'cover_url' => null,
        'owner_id' => $user->id
    ]);

    // Verificar se as comunidades têm cores de capa diferentes
    expect($community1->cover_url)->not->toBe($community2->cover_url);
});

it('preserves custom cover when set', function () {
    $user = User::factory()->create();
    
    $community = Community::factory()->create([
        'name' => 'technology',
        'cover_url' => 'custom-covers/my-cover.jpg',
        'owner_id' => $user->id
    ]);

    // Verificar se a capa personalizada é preservada
    expect($community->cover_url)->toBe('custom-covers/my-cover.jpg');
});

it('displays community cover in search results', function () {
    $user = User::factory()->create();
    
    $community = Community::factory()->create([
        'name' => 'technology',
        'cover_url' => null,
        'owner_id' => $user->id
    ]);

    $response = $this->actingAs($user)
        ->get('/search?q=technology&type=communities');

    $response->assertOk();
    
    // Verificar se os resultados incluem a comunidade com capa
    $response->assertInertia(fn ($page) => 
        $page->has('results.communities')
    );
});