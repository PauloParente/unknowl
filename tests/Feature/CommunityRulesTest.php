<?php

use App\Enums\CommunityRole;
use App\Models\Community;
use App\Models\User;

it('can update community rules as owner', function () {
    $owner = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $owner->id]);
    
    $newRules = [
        'Seja respeitoso com outros membros',
        'Não poste spam ou conteúdo inadequado',
        'Mantenha as discussões relevantes ao tópico',
    ];
    
    $response = $this->actingAs($owner)->patch("/communities/{$community->id}/rules", [
        'rules' => $newRules,
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    $community->refresh();
    expect($community->rules)->toBe($newRules);
});

it('can update community rules as admin', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $owner->id]);
    
    // Adicionar admin à comunidade
    $community->moderators()->attach($admin->id, [
        'role' => CommunityRole::ADMIN->value,
        'assigned_by' => $owner->id,
        'assigned_at' => now(),
        'is_active' => true,
    ]);
    
    $newRules = [
        'Nova regra criada pelo admin',
    ];
    
    $response = $this->actingAs($admin)->patch("/communities/{$community->id}/rules", [
        'rules' => $newRules,
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    $community->refresh();
    expect($community->rules)->toBe($newRules);
});

it('cannot update community rules as regular member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $owner->id]);
    
    // Adicionar membro à comunidade
    $community->members()->attach($member->id);
    
    $response = $this->actingAs($member)->patch("/communities/{$community->id}/rules", [
        'rules' => ['Regra não autorizada'],
    ]);
    
    $response->assertStatus(403);
});

it('validates rules input', function () {
    $owner = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $owner->id]);
    
    // Teste com regras vazias
    $response = $this->actingAs($owner)->patch("/communities/{$community->id}/rules", [
        'rules' => [],
    ]);
    
    $response->assertSessionHasErrors('rules');
    
    // Teste com regra muito longa
    $response = $this->actingAs($owner)->patch("/communities/{$community->id}/rules", [
        'rules' => [str_repeat('a', 501)], // 501 caracteres
    ]);
    
    $response->assertSessionHasErrors('rules.0');
});
