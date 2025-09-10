<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogPostController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // Blog Post Management
    Route::apiResource('blog-posts', BlogPostController::class);
    Route::post('blog-posts/{id}/set-priority', [BlogPostController::class, 'setPriority']);
});