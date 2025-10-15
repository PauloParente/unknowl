<?php

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use App\Enums\CommunityRole;

it('permite que administradores atualizem posts fixados', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);
    
    // Criar alguns posts na comunidade
    $post1 = Post::factory()->create(['community_id' => $community->id, 'user_id' => $user->id]);
    $post2 = Post::factory()->create(['community_id' => $community->id, 'user_id' => $user->id]);
    $post3 = Post::factory()->create(['community_id' => $community->id, 'user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->patch(route('communities.update-pinned-posts', $community), [
            'pinned_post_ids' => [$post1->id, $post2->id],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Posts fixados atualizados com sucesso!');

    // Verificar se os posts foram fixados
    expect($post1->fresh()->is_pinned)->toBeTrue();
    expect($post2->fresh()->is_pinned)->toBeTrue();
    expect($post3->fresh()->is_pinned)->toBeFalse();
});

it('não permite que usuários não autorizados atualizem posts fixados', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create();
    $post = Post::factory()->create(['community_id' => $community->id, 'user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->patch(route('communities.update-pinned-posts', $community), [
            'pinned_post_ids' => [$post->id],
        ]);

    $response->assertForbidden();
});

it('limita o número máximo de posts fixados a 3', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);
    
    $posts = Post::factory(5)->create(['community_id' => $community->id, 'user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->patch(route('communities.update-pinned-posts', $community), [
            'pinned_post_ids' => $posts->pluck('id')->toArray(),
        ]);

    $response->assertSessionHasErrors(['pinned_post_ids']);
});

it('não permite fixar posts de outras comunidades', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);
    $otherCommunity = Community::factory()->create();
    $otherPost = Post::factory()->create(['community_id' => $otherCommunity->id, 'user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->patch(route('communities.update-pinned-posts', $community), [
            'pinned_post_ids' => [$otherPost->id],
        ]);

    $response->assertSessionHasErrors(['pinned_post_ids']);
});

it('permite remover todos os posts fixados', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);
    $post = Post::factory()->create([
        'community_id' => $community->id,
        'user_id' => $user->id,
        'is_pinned' => true,
        'pinned_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->patch(route('communities.update-pinned-posts', $community), [
            'pinned_post_ids' => [],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Posts fixados atualizados com sucesso!');

    expect($post->fresh()->is_pinned)->toBeFalse();
    expect($post->fresh()->pinned_at)->toBeNull();
});

it('ordena posts fixados primeiro na listagem da comunidade', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['owner_id' => $user->id]);
    
    $post1 = Post::factory()->create(['community_id' => $community->id, 'user_id' => $user->id]);
    $post2 = Post::factory()->create(['community_id' => $community->id, 'user_id' => $user->id]);
    $post3 = Post::factory()->create(['community_id' => $community->id, 'user_id' => $user->id]);

    // Fixar post2
    $post2->update(['is_pinned' => true, 'pinned_at' => now()]);

    $response = $this->actingAs($user)
        ->get(route('communities.show', ['name' => $community->name]));

    $response->assertSuccessful();
    
    // Verificar se o post fixado aparece primeiro na página
    $response->assertInertia(fn ($page) => 
        $page->component('communities/Show')
            ->has('posts')
            ->where('posts.0.id', $post2->id)
    );
});