<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\ResourceController;
use App\Http\Controllers\Web\ReviewController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/browse');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/browse', [ResourceController::class, 'index'])->name('browse');
    Route::get('/upload', [ResourceController::class, 'create'])->name('upload');
    Route::post('/upload', [ResourceController::class, 'store'])->name('upload.store');
    Route::get('/shelf', [ProfileController::class, 'shelf'])->name('shelf');

    Route::get('/resources/{resource}', [ResourceController::class, 'show'])->name('resources.show');
    Route::post('/resources/{resource}/vote', [ResourceController::class, 'vote'])->name('resources.vote');
    Route::post('/resources/{resource}/download', [ResourceController::class, 'download'])->name('resources.download');
    Route::get('/resources/{resource}/file', [ResourceController::class, 'file'])->name('resources.file');
    Route::post('/resources/{resource}/report', [ResourceController::class, 'report'])->name('resources.report');

    Route::middleware('role:moderator,faculty')->prefix('review')->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->name('review');
        Route::post('/{resource}/approve', [ReviewController::class, 'approve'])->name('review.approve');
        Route::post('/{resource}/reject', [ReviewController::class, 'reject'])->name('review.reject');
        Route::post('/{resource}/dismiss', [ReviewController::class, 'dismiss'])->name('review.dismiss');
    });
});
