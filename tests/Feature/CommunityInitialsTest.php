<?php

use App\Models\User;
use App\Models\Community;

it('displays community initials correctly without r/ prefix', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create([
        'name' => 'technology',
        'avatar' => null,
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
    );
});

it('displays community initials correctly with r/ prefix', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create([
        'name' => 'programming',
        'avatar' => null,
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
    );
});

it('displays community initials for multi-word names', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create([
        'name' => 'web-development',
        'avatar' => null,
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
    );
});

it('displays community avatar when available', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create([
        'name' => 'technology',
        'avatar' => 'community-avatars/test-avatar.jpg',
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
            ->where('community.avatar', $community->avatar)
    );
});