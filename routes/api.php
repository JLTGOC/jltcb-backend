<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    DummyController
};

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group([
    'prefix' => 'auth',
], function ($route) {
    $route->post('/register', [AuthController::class, 'register']);
    $route->post('/login', [AuthController::class, 'login']);
    $route->post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::group([
    'prefix' => 'dummy',
    'middleware' => 'guest'
], function ($route) {
    $route->get('/reels', [DummyController::class, 'dummyReels']);
    $route->get('/articles', [DummyController::class, 'dummyArticles']);
});
