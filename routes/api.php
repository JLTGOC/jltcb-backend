<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ReelController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    DummyController
};

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::group([
    'prefix' => 'auth',
], function ($route) {
    // $route->post('/register', [AuthController::class, 'register']);
    $route->post('/login', [AuthController::class, 'login']);
    $route->post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Route::get('/reels', [ReelController::class, 'index']);
Route::get('/reels/{reel}', [ReelController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('reels', ReelController::class)->only(['store', 'update', 'destroy']);
});

// Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{article}', [ArticleController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::post('/articles/{article}', [ArticleController::class, 'update']);
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);
});

Route::group([
    'prefix' => 'home'
], function ($route) {
    $route->get('/reels', [ReelController::class, 'index']);
    $route->get('/articles', [ArticleController::class, 'index']);
});