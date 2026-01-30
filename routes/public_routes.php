<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReelController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\HomeController;

// Public Article Routes
Route::controller(ArticleController::class)->group(function () {
    Route::get('/articles', 'index');
    Route::get('/articles/{article}', 'show');
});

// Public Reel Routes
Route::controller(ReelController::class)->group(function () {
    Route::get('/reels', 'index');
    Route::get('/reels/{reel}', 'show');
});

Route::get('/home', [HomeController::class, 'home']);
