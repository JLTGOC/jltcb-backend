<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    ReelController,
    ArticleController,
    DashboardController,
    UserController,
    QuotationController,
    ChatController,
    QuotationChatController,
    ShipmentController
};
use League\CommonMark\Extension\SmartPunct\Quote;

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

    Route::controller(UserController::class)->group(function ($route) {
        $route->get('/user/show', 'show');
    });

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::group([
        'prefix' => 'quotation'
    ], function ($route) {
        $route->get('/enum-options', [QuotationController::class, 'enumQuotationOptions']);
        $route->get('/', [QuotationController::class, 'index']);
        $route->post('/', [QuotationController::class, 'store']);
        $route->get('/{referenceNumber}', [QuotationController::class, 'show']);
        $route->put('/{referenceNumber}', [QuotationController::class, 'update']);
    });

    // --- Standard Chat ---
    Route::get('/chats', [ChatController::class, 'index']); // Inbox
    Route::get('/chats/{conversation}', [ChatController::class, 'show']); // History

    // Reply to specific chat
    Route::post('/chats/{conversation}/messages', [ChatController::class, 'sendMessageToConversation']);

    // Message a person (finds or creates chat)
    Route::post('/users/{user}/messages', [ChatController::class, 'sendMessageToUser']);

    // --- Quotation Workflow ---
    // LeadAS sends card
    Route::post('/quotations/send-card', [QuotationChatController::class, 'sendQuotationCard']);

    // Client approves -> New Group Chat
    Route::post('/quotations/{id}/approve', [QuotationChatController::class, 'approveQuotation']);

    Route::post('/quotations/{quotation}/chat', [QuotationChatController::class, 'chatWithQuotation']);
    
    Route::post('/', [QuotationController::class, 'store']);
    Route::get('/{referenceNumber}', [QuotationController::class, 'show']);

    Route::post('/quotations/{quotation}/upload', [QuotationController::class, 'upload']);
    Route::group([
        'prefix' => 'shipment'
    ], function ($route) {
        $route->post('/', [ShipmentController::class, 'store']);
        $route->get('/{referenceNumber}', [ShipmentController::class, 'show']);
        $route->put('/{referenceNumber}', [ShipmentController::class, 'update']);
    });

    //temporary routes for quotation files
    Route::post('/quotations/{quotation}/upload', [QuotationController::class, 'upload']);
    Route::get('/quotations/{quotation}/showFile', [QuotationController::class, 'showFile']);
});
