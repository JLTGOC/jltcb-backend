<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReelController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\QuotationFileController;
use Illuminate\Support\Facades\Broadcast;

require __DIR__ . '/public_routes.php';

Route::post('auth/login', [AuthController::class, 'login']);

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::prefix('articles')->group(function () {
        Route::post('/', [ArticleController::class, 'store']);
        Route::post('{article}', [ArticleController::class, 'update']);
        Route::delete('{article}', [ArticleController::class, 'destroy']);
    });

    Route::apiResource('reels', ReelController::class)->only(['store', 'update', 'destroy']);

    Route::apiResource('users', UserController::class)->only(['index', 'show', 'update']);
    Route::put('/users/{user}/change-password', [UserController::class, 'changePassword']);
    Route::post('/users/{user}/change-profile', [UserController::class, 'changeProfile']);

    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::get('quotations/enum-options', [QuotationController::class, 'enumQuotationOptions']);
    Route::apiResource('quotations', QuotationController::class)->except(['update']);
    Route::post('/quotations/{quotation}', [QuotationController::class, 'update']);

    
    //Temporary routes for quotation files
    Route::post('/quotations/{quotation}/upload', [QuotationController::class, 'upload']);
    Route::apiResource('quotations.files', QuotationFileController::class)->only(['index', 'show', 'update'])->scoped();

    Route::prefix('conversations')->group(function () {
        Route::get('', [ChatController::class, 'index']); // Inbox
        Route::get('/{conversation}', [ChatController::class, 'show']);
        Route::get('{conversation}/messages', [ChatController::class, 'indexMessages']); // History
        Route::post('{conversation}', [ChatController::class, 'sendMessage']); // Message a conversation
        Route::post('/{conversation}/read', [ChatController::class, 'markAsRead']); // For chat viewer's unread count update
    });

    // Quotation Chat
    Route::post('quotations/{quotation}/chat', [ChatController::class, 'chatWithQuotation']);

    // Shipment Routes
    Route::apiResource('shipments', ShipmentController::class)->only(['store', 'show', 'update', 'index']);
});
