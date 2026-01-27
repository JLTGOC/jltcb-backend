<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReelController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\DashboardController;

require __DIR__ . '/public_routes.php';
// test webhook

Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::controller(ArticleController::class)->group(function () {
        Route::post('/articles', 'store');
        Route::post('/articles/{article}', 'update');
        Route::delete('/articles/{article}', 'destroy');
    });

    Route::apiResource('reels', ReelController::class)->only(['store', 'update', 'destroy']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
});
