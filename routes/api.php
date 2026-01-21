<?php

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

Route::get('/reels', [ReelController::class, 'index']);
Route::get('/reels/{reel}', [ReelController::class, 'show']);

Route::group([
    'prefix' => 'reels',
    'middleware' => 'auth:sanctum'
], function ($route) {
    $route->post('/', [ReelController::class, 'store']);
    $route->put('/{reel}', [ReelController::class, 'update']);
    $route->delete('/{reel}', [ReelController::class, 'destroy']);
});

Route::group([
    'prefix' => 'dummy',
    'middleware' => 'allow.guest'
], function ($route) {
    $route->get('/reels', [DummyController::class, 'dummyReels']);
    $route->get('/articles', [DummyController::class, 'dummyArticles']);
});