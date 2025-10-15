<?php

use App\Models\Community;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('permite que o dono da comunidade atualize a capa', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);

    $file = UploadedFile::fake()->image('cover.jpg', 1920, 384);

    $response = $this->actingAs($user)
        ->post(route('communities.update-cover', $community), [
            'cover' => $file,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Capa atualizada com sucesso!');

    $community->refresh();
    expect($community->cover_url)->not->toBeNull();
    expect($community->cover_url)->toContain('communities/covers/');
    
    Storage::disk('public')->assertExists($community->cover_url);
});

it('permite que um administrador da comunidade atualize a capa', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $owner->id]);

    // Adicionar admin como moderador
    $community->moderators()->attach($admin->id, [
        'role' => \App\Enums\CommunityRole::ADMIN->value,
        'assigned_by' => $owner->id,
        'assigned_at' => now(),
        'is_active' => true,
    ]);

    $file = UploadedFile::fake()->image('cover.jpg', 1920, 384);

    $response = $this->actingAs($admin)
        ->post(route('communities.update-cover', $community), [
            'cover' => $file,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Capa atualizada com sucesso!');

    $community->refresh();
    expect($community->cover_url)->not->toBeNull();
    expect($community->cover_url)->toContain('communities/covers/');
    
    Storage::disk('public')->assertExists($community->cover_url);
});

it('não permite que um membro comum atualize a capa', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $owner->id]);

    // Adicionar como membro comum
    $community->members()->attach($member->id);

    $file = UploadedFile::fake()->image('cover.jpg', 1920, 384);

    $response = $this->actingAs($member)
        ->post(route('communities.update-cover', $community), [
            'cover' => $file,
        ]);

    $response->assertForbidden();
});

it('não permite que usuários não autenticados atualizem a capa', function () {
    $community = Community::factory()->create();

    $file = UploadedFile::fake()->image('cover.jpg', 1920, 384);

    $response = $this->post(route('communities.update-cover', $community), [
        'cover' => $file,
    ]);

    $response->assertRedirect(route('login'));
});

it('valida que o arquivo é uma imagem', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);

    $file = UploadedFile::fake()->create('document.pdf', 1000);

    $response = $this->actingAs($user)
        ->post(route('communities.update-cover', $community), [
            'cover' => $file,
        ]);

    $response->assertSessionHasErrors(['cover']);
});

it('valida que o arquivo não é muito grande', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);

    $file = UploadedFile::fake()->image('cover.jpg')->size(6000); // 6MB

    $response = $this->actingAs($user)
        ->post(route('communities.update-cover', $community), [
            'cover' => $file,
        ]);

    $response->assertSessionHasErrors(['cover']);
});

it('valida que o arquivo é obrigatório', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)
        ->post(route('communities.update-cover', $community), []);

    $response->assertSessionHasErrors(['cover']);
});

it('substitui a capa anterior quando uma nova é enviada', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);

    // Upload da primeira capa
    $firstFile = UploadedFile::fake()->image('first-cover.jpg', 1920, 384);
    $this->actingAs($user)
        ->post(route('communities.update-cover', $community), [
            'cover' => $firstFile,
        ]);

    $community->refresh();
    $firstCoverPath = $community->cover_url;
    expect($firstCoverPath)->not->toBeNull();
    
    Storage::disk('public')->assertExists($firstCoverPath);

    // Upload da segunda capa
    $secondFile = UploadedFile::fake()->image('second-cover.jpg', 1920, 384);
    $this->actingAs($user)
        ->post(route('communities.update-cover', $community), [
            'cover' => $secondFile,
        ]);

    $community->refresh();
    $secondCoverPath = $community->cover_url;
    expect($secondCoverPath)->not->toBeNull();
    expect($secondCoverPath)->not->toBe($firstCoverPath);
    
    // A primeira capa deve ter sido deletada
    Storage::disk('public')->assertMissing($firstCoverPath);
    // A segunda capa deve existir
    Storage::disk('public')->assertExists($secondCoverPath);
});