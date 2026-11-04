<?php

use App\Http\Controllers\AISettingsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GraphController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('welcome'));

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/capture', [NoteController::class, 'quickCapture'])->name('capture');
    Route::post('/notes/{id}/restore', [NoteController::class, 'restore'])->name('notes.restore');
    Route::delete('/notes/{id}/force', [NoteController::class, 'forceDelete'])->name('notes.force');
    Route::post('/notes/{note}/toggle/{flag}', [NoteController::class, 'toggle'])->name('notes.toggle');
    Route::post('/notes/{note}/summarize', [NoteController::class, 'summarize'])->name('notes.summarize');
    Route::post('/notes/{note}/links', [NoteController::class, 'link'])->name('notes.link');
    Route::resource('notes', NoteController::class);

    Route::get('/graph', [GraphController::class, 'index'])->name('graph');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/brain', [ConversationController::class, 'index'])->name('brain.index');
    Route::post('/brain/conversations', [ConversationController::class, 'store'])->name('brain.store');
    Route::get('/brain/conversations/{conversation}', [ConversationController::class, 'show'])->name('brain.show');
    Route::delete('/brain/conversations/{conversation}', [ConversationController::class, 'destroy'])->name('brain.destroy');
    Route::post('/brain/conversations/{conversation}/messages', [MessageController::class, 'store'])->middleware('throttle:20,1')->name('brain.messages.store');
    Route::get('/brain/messages/{message}/save-as-note', [MessageController::class, 'saveAsNote'])->name('brain.messages.save');

    Route::get('/settings/ai', [AISettingsController::class, 'edit'])->name('settings.ai');
    Route::put('/settings/ai', [AISettingsController::class, 'update'])->name('settings.ai.update');
    Route::post('/settings/ai/test', [AISettingsController::class, 'testConnection'])->name('settings.ai.test');
    Route::post('/settings/ai/test-embeddings', [AISettingsController::class, 'testEmbeddings'])->name('settings.ai.test-embeddings');
    Route::post('/settings/ai/reindex', [AISettingsController::class, 'reindex'])->middleware('throttle:3,1')->name('settings.ai.reindex');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
