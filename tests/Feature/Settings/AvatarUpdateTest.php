<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('can upload avatar for authenticated user', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->post('/settings/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 400, 400)
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'avatar-updated');
    
    $user = $user->fresh();
    
    // Verifica que o avatar foi salvo (não null e começa com 'avatars/')
    expect($user->avatar)->not->toBeNull();
    expect($user->avatar)->toStartWith('avatars/');
    
    Storage::disk('public')->assertExists($user->avatar);
});

it('cannot upload avatar without authentication', function () {
    $response = $this->post('/settings/profile/avatar', [
        'avatar' => UploadedFile::fake()->image('avatar.jpg')
    ]);

    $response->assertRedirect('/login');
});

it('validates avatar file is required', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->post('/settings/profile/avatar', []);

    $response->assertSessionHasErrors(['avatar']);
});

it('validates avatar file type', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->post('/settings/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('document.pdf', 1000)
        ]);

    $response->assertSessionHasErrors(['avatar']);
});

it('validates avatar file size', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->post('/settings/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg')->size(3000) // 3MB
        ]);

    $response->assertSessionHasErrors(['avatar']);
});

it('validates avatar dimensions', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->post('/settings/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 50, 50) // Too small
        ]);

    $response->assertSessionHasErrors(['avatar']);
});

it('can delete avatar', function () {
    $user = User::factory()->create(['avatar' => 'avatars/old-avatar.jpg']);
    
    Storage::disk('public')->put($user->avatar, 'fake image content');
    
    $response = $this->actingAs($user)
        ->delete('/settings/profile/avatar');

    $response->assertRedirect();
    $response->assertSessionHas('status', 'avatar-deleted');
    
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'avatar' => null
    ]);
    
    Storage::disk('public')->assertMissing('avatars/old-avatar.jpg');
});

it('replaces existing avatar when uploading new one', function () {
    $user = User::factory()->create(['avatar' => 'avatars/old-avatar.jpg']);
    
    Storage::disk('public')->put($user->avatar, 'fake old image content');
    
    $response = $this->actingAs($user)
        ->post('/settings/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('new-avatar.jpg', 400, 400)
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'avatar-updated');
    
    // Old avatar should be deleted
    Storage::disk('public')->assertMissing('avatars/old-avatar.jpg');
    
    // New avatar should exist
    Storage::disk('public')->assertExists($user->fresh()->avatar);
});

it('accepts valid image formats', function () {
    $user = User::factory()->create();
    
    $validFormats = ['jpeg', 'png', 'jpg', 'gif', 'webp'];
    
    foreach ($validFormats as $format) {
        $response = $this->actingAs($user)
            ->post('/settings/profile/avatar', [
                'avatar' => UploadedFile::fake()->image("avatar.{$format}", 400, 400)
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'avatar-updated');
        
        // Reset user avatar for next test
        $user->update(['avatar' => null]);
    }
});