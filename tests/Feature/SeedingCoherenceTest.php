<?php

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
// O TestCase base já é aplicado aos testes de Feature via tests/Pest.php.

it('seeds coherent data with communities, posts, comments and votes', function () {
    // Seed database
    $this->artisan('db:seed')->assertSuccessful();

    // Basic presence
    expect(User::count())->toBeGreaterThanOrEqual(50);
    expect(Community::count())->toBeGreaterThanOrEqual(6);
    expect(Post::count())->toBeGreaterThan(0);
    expect(Comment::count())->toBeGreaterThan(0);

    // Memberships
    $community = Community::with('members')->inRandomOrder()->first();
    expect($community->members)->not->toBeEmpty();

    // Posts belong to communities and authors
    $post = Post::with(['community', 'author'])->inRandomOrder()->first();
    expect($post->community)->not->toBeNull();
    expect($post->author)->not->toBeNull();

    // Comments chronology after post creation
    $comment = Comment::where('post_id', $post->id)->inRandomOrder()->first();
    if ($comment) {
        expect($comment->created_at->greaterThanOrEqualTo($post->created_at))->toBeTrue();
    }

    // Votes affect score (non-strict, just sanity)
    $post->refresh();
    expect(is_int($post->score))->toBeTrue();
});


