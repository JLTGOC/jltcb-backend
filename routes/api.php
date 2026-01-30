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
    QuotationChatController
};

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
        $route->get('/service-options', [QuotationController::class, 'indexServiceOptions']);
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

    Route::post('/quotations/{quotation}/chatLeadAs', [QuotationChatController::class, 'chatLeadAs']);
});