<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('profile', function () {
    return Inertia::render('Profile');
})->middleware(['auth', 'verified'])->name('profile');

Route::get('dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('explore', [App\Http\Controllers\ExploreController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('explore');

Route::get('popular', function () {
    return Inertia::render('Popular');
})->middleware(['auth', 'verified'])->name('popular');

Route::get('onboarding/suggestions', [App\Http\Controllers\OnboardingController::class, 'suggestions'])
    ->middleware(['auth', 'verified'])
    ->name('onboarding.suggestions');

Route::get('search', [App\Http\Controllers\SearchController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('search');

Route::get('search/autocomplete', [App\Http\Controllers\SearchController::class, 'autocomplete'])
    ->middleware(['auth', 'verified'])
    ->name('search.autocomplete');

Route::post('communities/{community}/follow', [App\Http\Controllers\CommunityFollowController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('communities.follow');
Route::delete('communities/{community}/follow', [App\Http\Controllers\CommunityFollowController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('communities.unfollow');

Route::get('communities/following', [App\Http\Controllers\CommunityFollowingController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('communities.following');

Route::get('posts/{id}', [App\Http\Controllers\PostController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('posts.show');

Route::middleware(['auth'])->group(function () {
    Route::post('posts', [App\Http\Controllers\PostController::class, 'store'])
        ->name('posts.store');
    
    // Comentários em posts
    Route::post('posts/{post}/comments', [App\Http\Controllers\CommentController::class, 'store'])
        ->name('posts.comments.store');
    Route::patch('comments/{comment}', [App\Http\Controllers\CommentController::class, 'update'])
        ->name('comments.update');

    // Rotas para votos em posts
    Route::post('posts/{post}/vote', [App\Http\Controllers\PostVoteController::class, 'vote'])
        ->name('posts.vote');
    Route::delete('posts/{post}/vote', [App\Http\Controllers\PostVoteController::class, 'removeVote'])
        ->name('posts.vote.remove');
    
    // Rotas para votos em comentários
    Route::post('comments/{comment}/vote', [App\Http\Controllers\CommentVoteController::class, 'vote'])
        ->name('comments.vote');
    Route::delete('comments/{comment}/vote', [App\Http\Controllers\CommentVoteController::class, 'removeVote'])
        ->name('comments.vote.remove');
});

// Rotas de comunidades
Route::get('communities/create', [App\Http\Controllers\CommunityController::class, 'create'])
    ->middleware(['auth', 'verified'])
    ->name('communities.create');

Route::post('communities', [App\Http\Controllers\CommunityController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('communities.store');

Route::get('communities/check-name', [App\Http\Controllers\CommunityController::class, 'checkNameAvailability'])
    ->middleware(['auth', 'verified'])
    ->name('communities.check-name');

Route::get('r/{name}', [App\Http\Controllers\CommunityController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('communities.show');

// Rotas para atualizar configurações da comunidade
Route::middleware(['auth', 'verified'])->group(function () {
    Route::patch('communities/{community}/settings', [App\Http\Controllers\CommunityController::class, 'updateSettings'])
        ->name('communities.update-settings');
    
    Route::patch('communities/{community}/rules', [App\Http\Controllers\CommunityController::class, 'updateRules'])
        ->name('communities.update-rules');
    
    Route::patch('communities/{community}/pinned-posts', [App\Http\Controllers\CommunityController::class, 'updatePinnedPosts'])
        ->name('communities.update-pinned-posts');
    
    Route::post('communities/{community}/cover', [App\Http\Controllers\CommunityController::class, 'updateCover'])
        ->name('communities.update-cover');
    
    Route::post('communities/{community}/avatar', [App\Http\Controllers\CommunityController::class, 'updateAvatar'])
        ->name('communities.update-avatar');
    
    Route::delete('communities/{community}/avatar', [App\Http\Controllers\CommunityController::class, 'deleteAvatar'])
        ->name('communities.delete-avatar');
});

Route::get('u/{username}', [App\Http\Controllers\UserController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('users.show');

// Rotas de moderação de comunidades
Route::middleware(['auth', 'verified'])->prefix('communities/{community}')->group(function () {
    // Dashboard de moderação
    Route::get('moderation', [App\Http\Controllers\CommunityModerationController::class, 'dashboard'])
        ->middleware('community.moderation:moderate')
        ->name('communities.moderation.dashboard');
    
    // Gerenciar moderadores
    Route::get('moderation/moderators', [App\Http\Controllers\CommunityModerationController::class, 'moderators'])
        ->middleware('community.moderation:manage_moderators,admin')
        ->name('communities.moderation.moderators');
    
    Route::post('moderation/moderators/assign', [App\Http\Controllers\CommunityModerationController::class, 'assignModerator'])
        ->middleware('community.moderation:manage_moderators,admin')
        ->name('communities.moderation.moderators.assign');
    
    Route::delete('moderation/moderators/remove', [App\Http\Controllers\CommunityModerationController::class, 'removeModerator'])
        ->middleware('community.moderation:manage_moderators,admin')
        ->name('communities.moderation.moderators.remove');
    
    Route::patch('moderation/moderators/change-role', [App\Http\Controllers\CommunityModerationController::class, 'changeModeratorRole'])
        ->middleware('community.moderation:manage_moderators,admin')
        ->name('communities.moderation.moderators.change-role');
    
    // Buscar usuários para designar como moderadores
    Route::get('moderation/search-users', [App\Http\Controllers\CommunityModerationController::class, 'searchUsers'])
        ->middleware('community.moderation:manage_moderators,admin')
        ->name('communities.moderation.search-users');
    
    // Gerenciar bans
    Route::post('moderation/ban-user', [App\Http\Controllers\CommunityModerationController::class, 'banUser'])
        ->middleware('community.moderation:ban_users,moderator')
        ->name('communities.moderation.ban-user');
    
    Route::post('moderation/unban-user', [App\Http\Controllers\CommunityModerationController::class, 'unbanUser'])
        ->middleware('community.moderation:ban_users,moderator')
        ->name('communities.moderation.unban-user');
    
    // Logs de moderação
    Route::get('moderation/logs', [App\Http\Controllers\CommunityModerationController::class, 'logs'])
        ->middleware('community.moderation:view_logs,moderator')
        ->name('communities.moderation.logs');
});

// Rotas de moderação de conteúdo
Route::middleware(['auth', 'verified'])->group(function () {
    // Moderação de posts
    Route::patch('posts/{post}/pin', [App\Http\Controllers\ContentModerationController::class, 'togglePinPost'])
        ->middleware('moderation.action:pin_post')
        ->name('posts.pin');
    
    Route::patch('posts/{post}/lock', [App\Http\Controllers\ContentModerationController::class, 'toggleLockPost'])
        ->middleware('moderation.action:lock_post')
        ->name('posts.lock');
    
    Route::delete('posts/{post}/remove', [App\Http\Controllers\ContentModerationController::class, 'removePost'])
        ->middleware('moderation.action:remove_post')
        ->name('posts.remove');
    
    Route::patch('posts/{post}/restore', [App\Http\Controllers\ContentModerationController::class, 'restorePost'])
        ->middleware('moderation.action:restore_post')
        ->name('posts.restore');
    
    // Moderação de comentários
    Route::delete('comments/{comment}/remove', [App\Http\Controllers\ContentModerationController::class, 'removeComment'])
        ->middleware('moderation.action:remove_comment')
        ->name('comments.remove');
    
    Route::patch('comments/{comment}/restore', [App\Http\Controllers\ContentModerationController::class, 'restoreComment'])
        ->middleware('moderation.action:restore_comment')
        ->name('comments.restore');
    
    // Ações de usuário
    Route::post('moderation/warn-user', [App\Http\Controllers\ContentModerationController::class, 'warnUser'])
        ->middleware('moderation.action:warn_user')
        ->name('moderation.warn-user');
    
    Route::post('moderation/mute-user', [App\Http\Controllers\ContentModerationController::class, 'toggleMuteUser'])
        ->middleware('moderation.action:mute_user')
        ->name('moderation.mute-user');
    
    Route::post('moderation/approve-member', [App\Http\Controllers\ContentModerationController::class, 'approveMember'])
        ->middleware('moderation.action:approve_member')
        ->name('moderation.approve-member');
    
    Route::post('moderation/reject-member', [App\Http\Controllers\ContentModerationController::class, 'rejectMember'])
        ->middleware('moderation.action:reject_member')
        ->name('moderation.reject-member');
    
    Route::post('moderation/remove-member', [App\Http\Controllers\ContentModerationController::class, 'removeMember'])
        ->middleware('moderation.action:remove_member')
        ->name('moderation.remove-member');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/chat.php';

