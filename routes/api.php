<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [MeController::class, 'show']);
    Route::get('/me/shelf', [MeController::class, 'shelf']);
    Route::get('/departments', [MeController::class, 'departments']);

    Route::get('/resources', [ResourceController::class, 'index']);
    Route::post('/resources', [ResourceController::class, 'store']);
    Route::get('/resources/{resource}', [ResourceController::class, 'show']);
    Route::post('/resources/{resource}/vote', [ResourceController::class, 'vote']);
    Route::post('/resources/{resource}/unlock', [ResourceController::class, 'unlock']);
    Route::get('/resources/{resource}/file', [ResourceController::class, 'file']);
    Route::post('/resources/{resource}/report', [ResourceController::class, 'report']);

    Route::middleware('role:moderator,faculty')->prefix('review')->group(function () {
        Route::get('/queue', [ReviewController::class, 'queue']);
        Route::post('/{resource}/approve', [ReviewController::class, 'approve']);
        Route::post('/{resource}/reject', [ReviewController::class, 'reject']);
        Route::post('/{resource}/dismiss-reports', [ReviewController::class, 'dismissReports']);
    });
});
