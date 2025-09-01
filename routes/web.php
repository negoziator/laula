<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Chat/Index');
})->name('home');

Route::get('/chat', function () {
    return Inertia::render('Chat/Index');
})->name('chat');

Route::get('/aula', function () {
    return Inertia::render('Aula/Dashboard');
})->name('aula');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// API Routes for chat functionality
Route::prefix('api')->group(function () {
    Route::post('/chat', [App\Http\Controllers\Api\ChatController::class, 'chat'])->name('api.chat');
    Route::get('/models', [App\Http\Controllers\Api\ChatController::class, 'models'])->name('api.models');
    Route::get('/agents', [App\Http\Controllers\Api\ChatController::class, 'agents'])->name('api.agents');
    Route::get('/conversations', [App\Http\Controllers\Api\ChatController::class, 'conversations'])->name('api.conversations');
    Route::get('/conversations/{conversation}', [App\Http\Controllers\Api\ChatController::class, 'conversation'])->name('api.conversation');
    Route::get('/health', [App\Http\Controllers\HealthController::class, 'check'])->name('api.health');
    
    // Aula API routes
    Route::prefix('aula')->group(function () {
        Route::get('/status', [App\Http\Controllers\Api\AulaController::class, 'status'])->name('api.aula.status');
        Route::get('/children', [App\Http\Controllers\Api\AulaController::class, 'children'])->name('api.aula.children');
        Route::post('/active-child', [App\Http\Controllers\Api\AulaController::class, 'setActiveChild'])->name('api.aula.active-child');
        Route::get('/daily-overview', [App\Http\Controllers\Api\AulaController::class, 'dailyOverview'])->name('api.aula.daily-overview');
        Route::get('/messages', [App\Http\Controllers\Api\AulaController::class, 'messages'])->name('api.aula.messages');
        Route::get('/calendar', [App\Http\Controllers\Api\AulaController::class, 'calendar'])->name('api.aula.calendar');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
