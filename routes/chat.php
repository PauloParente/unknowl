<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;

/*
|--------------------------------------------------------------------------
| Chat Routes
|--------------------------------------------------------------------------
|
| Here are all the routes for the chat system. These routes handle
| chat management, messaging, and real-time communication features.
|
*/

Route::middleware(['auth', 'verified'])->prefix('chat')->name('chat.')->group(function () {
    
    // Chat Management Routes
    Route::get('/', [ChatController::class, 'index'])->name('index');
    Route::get('/create', function () {
        return Inertia::render('Chat/Create');
    })->name('create');
    Route::post('/', [ChatController::class, 'store'])->name('store');
    Route::get('/search/users', [ChatController::class, 'searchUsers'])->name('search-users');
    
    // Chat specific routes (must come after specific routes)
    Route::get('/{chat}', [ChatController::class, 'show'])->name('show');
    Route::patch('/{chat}', [ChatController::class, 'update'])->name('update');
    Route::delete('/{chat}', [ChatController::class, 'destroy'])->name('destroy');
    
    // Chat Actions
    Route::post('/{chat}/archive', [ChatController::class, 'archive'])->name('archive');
    Route::post('/{chat}/unarchive', [ChatController::class, 'unarchive'])->name('unarchive');
    
    // Message Routes
    Route::prefix('{chat}/messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::post('/', [MessageController::class, 'store'])->name('store');
        Route::patch('/{message}', [MessageController::class, 'update'])->name('update');
        Route::delete('/{message}', [MessageController::class, 'destroy'])->name('destroy');
        
        // Message Actions
        Route::post('/{message}/read', [MessageController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [MessageController::class, 'markAllAsRead'])->name('read-all');
        Route::get('/unread-count', [MessageController::class, 'getUnreadCount'])->name('unread-count');
        Route::get('/search', [MessageController::class, 'search'])->name('search');
    });

    // Global unread counts route
    Route::get('/messages/unread-counts', [MessageController::class, 'getUnreadCounts'])->name('messages.unread-counts');
});

// Alternative routes for better UX (without /chat prefix)
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Main chat routes
    Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('/chats/create', function () {
        return Inertia::render('Chat/Create');
    })->name('chats.create');
    Route::post('/chats', [ChatController::class, 'store'])->name('chats.store');
    Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
    Route::patch('/chats/{chat}', [ChatController::class, 'update'])->name('chats.update');
    Route::delete('/chats/{chat}', [ChatController::class, 'destroy'])->name('chats.destroy');
    
    // Chat actions
    Route::post('/chats/{chat}/archive', [ChatController::class, 'archive'])->name('chats.archive');
    Route::post('/chats/{chat}/unarchive', [ChatController::class, 'unarchive'])->name('chats.unarchive');
    
    // Message routes
    Route::prefix('chats/{chat}/messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::post('/', [MessageController::class, 'store'])->name('store');
        Route::patch('/{message}', [MessageController::class, 'update'])->name('update');
        Route::delete('/{message}', [MessageController::class, 'destroy'])->name('destroy');
        
        // Message actions
        Route::post('/{message}/read', [MessageController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [MessageController::class, 'markAllAsRead'])->name('read-all');
        Route::get('/unread-count', [MessageController::class, 'getUnreadCount'])->name('unread-count');
        Route::get('/search', [MessageController::class, 'search'])->name('search');
    });
});
