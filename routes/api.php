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

require __DIR__ . '/public_routes.php';

Route::post('auth/login', [AuthController::class, 'login']);

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

    Route::apiResource('users', UserController::class)->only(['index', 'show']);

    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::get('quotations/enum-options', [QuotationController::class, 'enumQuotationOptions']);
    Route::apiResource('quotations', QuotationController::class)->except(['destroy', 'update']);
    Route::post('/quotations/{quotation}', [QuotationController::class, 'update']);

    
    //Temporary routes for quotation files
    Route::post('/quotations/{quotation}/upload', [QuotationController::class, 'upload']);
    Route::get('/quotations/{quotation}/files', [QuotationFileController::class, 'index']);
    Route::get('/quotations/{quotation}/files/{file}', [QuotationFileController::class, 'show'])
        ->scopeBindings();

    Route::prefix('conversations')->group(function () {
        Route::get('', [ChatController::class, 'index']); // Inbox
        Route::get('{conversation}/messages', [ChatController::class, 'indexMessages']); // History
        Route::post('{conversation}', [ChatController::class, 'sendMessage']); // Message a conversation
    });

    // Quotation Chat
    Route::post('quotations/{quotation}/chat', [ChatController::class, 'chatWithQuotation']);

    // Shipment Routes
    Route::apiResource('shipments', ShipmentController::class)->only(['store', 'show', 'update']);
});
