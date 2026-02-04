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
use App\Http\Controllers\QuotationChatController;

require __DIR__ . '/public_routes.php';

Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::apiResource('articles', ArticleController::class)->only(['store', 'update', 'destroy']);

    Route::apiResource('reels', ReelController::class)->only(['store', 'update', 'destroy']);

    Route::apiResource('users', UserController::class)->only(['show']);

    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::get('quotations/enum-options', [QuotationController::class, 'enumQuotationOptions']);
    Route::apiResource('quotations', QuotationController::class);
    
    //Temporary routes for quotation files
    Route::post('/quotations/{quotation}/upload', [QuotationController::class, 'upload']);
    Route::get('/quotations/{quotation}/showFile', [QuotationController::class, 'showFile']);

    Route::prefix('chats')->group(function () {
        Route::get('/', [ChatController::class, 'index']); // Inbox
        Route::get('/{conversation}', [ChatController::class, 'show']); // History
        Route::post('/{conversation}/messages', [ChatController::class, 'sendMessageToGroup']); // Message a conversation
    });

    // Message a group
    Route::post('/groups/{conversation}/messages', [ChatController::class, 'sendMessageToGroup']);

    // Message a user
    Route::post('/users/{user}/messages', [ChatController::class, 'sendMessageToUser']);

    // Quotation Chat
    Route::post('/quotations/{quotation}/chat', [QuotationChatController::class, 'chatWithQuotation']);

    Route::apiResource('shipments', ShipmentController::class)->only(['store', 'show', 'update']);
});
