<?php

use App\Models\User;
use App\Models\Community;
use App\Enums\CommunityRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('can upload avatar for community by owner', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);
    
    $response = $this->actingAs($user)
        ->post("/communities/{$community->id}/avatar", [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 400, 400)
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'avatar-updated');
    
    $community = $community->fresh();
    
    // Verifica que o avatar foi salvo (não null e começa com 'community-avatars/')
    expect($community->avatar)->not->toBeNull();
    expect($community->avatar)->toStartWith('community-avatars/');
    
    Storage::disk('public')->assertExists($community->avatar);
});

it('can upload avatar for community by admin', function () {
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
    
    $response = $this->actingAs($admin)
        ->post("/communities/{$community->id}/avatar", [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 400, 400)
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'avatar-updated');
    
    $community = $community->fresh();
    expect($community->avatar)->not->toBeNull();
    expect($community->avatar)->toStartWith('community-avatars/');
    
    Storage::disk('public')->assertExists($community->avatar);
});

it('cannot upload avatar for community by regular member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $owner->id]);
    
    // Adicionar membro à comunidade
    $community->members()->attach($member->id);
    
    $response = $this->actingAs($member)
        ->post("/communities/{$community->id}/avatar", [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 400, 400)
        ]);

    $response->assertForbidden();
});

it('cannot upload avatar for community without authentication', function () {
    $community = Community::factory()->create();
    
    $response = $this->post("/communities/{$community->id}/avatar", [
        'avatar' => UploadedFile::fake()->image('avatar.jpg')
    ]);

    $response->assertRedirect('/login');
});

it('validates avatar file is required', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);
    
    $response = $this->actingAs($user)
        ->post("/communities/{$community->id}/avatar", []);

    $response->assertSessionHasErrors(['avatar']);
});

it('validates avatar file type', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);
    
    $response = $this->actingAs($user)
        ->post("/communities/{$community->id}/avatar", [
            'avatar' => UploadedFile::fake()->create('document.pdf', 1000)
        ]);

    $response->assertSessionHasErrors(['avatar']);
});

it('validates avatar file size', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);
    
    $response = $this->actingAs($user)
        ->post("/communities/{$community->id}/avatar", [
            'avatar' => UploadedFile::fake()->image('avatar.jpg')->size(3000) // 3MB
        ]);

    $response->assertSessionHasErrors(['avatar']);
});

it('validates avatar dimensions', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);
    
    $response = $this->actingAs($user)
        ->post("/communities/{$community->id}/avatar", [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 50, 50) // Too small
        ]);

    $response->assertSessionHasErrors(['avatar']);
});

it('can delete avatar', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create([
        'owner_id' => $user->id,
        'avatar' => 'community-avatars/old-avatar.jpg'
    ]);
    
    Storage::disk('public')->put($community->avatar, 'fake image content');
    
    $response = $this->actingAs($user)
        ->delete("/communities/{$community->id}/avatar");

    $response->assertRedirect();
    $response->assertSessionHas('status', 'avatar-deleted');
    
    $community = $community->fresh();
    expect($community->getAttributes()['avatar'])->toBeNull();
    
    Storage::disk('public')->assertMissing('community-avatars/old-avatar.jpg');
});

it('replaces existing avatar when uploading new one', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create([
        'owner_id' => $user->id,
        'avatar' => 'community-avatars/old-avatar.jpg'
    ]);
    
    Storage::disk('public')->put($community->avatar, 'fake old image content');
    
    $response = $this->actingAs($user)
        ->post("/communities/{$community->id}/avatar", [
            'avatar' => UploadedFile::fake()->image('new-avatar.jpg', 400, 400)
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'avatar-updated');
    
    // Old avatar should be deleted
    Storage::disk('public')->assertMissing('community-avatars/old-avatar.jpg');
    
    // New avatar should exist
    $community = $community->fresh();
    Storage::disk('public')->assertExists($community->avatar);
});

it('accepts valid image formats', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);
    
    $validFormats = ['jpeg', 'png', 'jpg', 'gif', 'webp'];
    
    foreach ($validFormats as $format) {
        $response = $this->actingAs($user)
            ->post("/communities/{$community->id}/avatar", [
                'avatar' => UploadedFile::fake()->image("avatar.{$format}", 400, 400)
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'avatar-updated');
        
        // Reset community avatar for next test
        $community->update(['avatar' => null]);
    }
});