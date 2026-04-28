<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BulkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/blogs', [BlogController::class, 'index'])->name('blogs.index');
    Route::post('/blogs/connect', [BlogController::class, 'connect'])->name('blogs.connect');
    Route::post('/blogs/switch', [BlogController::class, 'switchBlog'])->name('blogs.switch');

    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::post('/posts/{post}/toggle-status', [PostController::class, 'toggleStatus'])->name('posts.toggle-status');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    Route::get('/labels', [LabelController::class, 'index'])->name('labels.index');
    Route::put('/labels/{label}/rename', [LabelController::class, 'rename'])->name('labels.rename');
    Route::post('/labels/merge', [LabelController::class, 'merge'])->name('labels.merge');
    Route::delete('/labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');

    Route::post('/bulk', [BulkController::class, 'handle'])->name('bulk.handle');
    Route::get('/bulk/{bulkOperation}/status', [BulkController::class, 'status'])->name('bulk.status');
});

require __DIR__.'/settings.php';
