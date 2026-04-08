<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReelController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BillingConfigController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DetailsConfigController;
use App\Http\Controllers\IssuedQuotationController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\QuotationFileController;
use App\Http\Controllers\JobOrderController;
use App\Http\Controllers\QuotationFieldController;
use App\Http\Controllers\QuotationTemplateController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StandardConfigurationController;
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

    Route::get('/users/account-specialists', [UserController::class, 'indexAccountSpecialists']);
    Route::get('/users/clients', [UserController::class, 'indexClientAccounts']);
    Route::get('/users/clients/{client}/shipments', [UserController::class, 'indexClientShipments']);
    Route::apiResource('users', UserController::class)->only(['show', 'update']);
    Route::put('/users/{user}/change-password', [UserController::class, 'changePassword']);
    Route::post('/users/{user}/change-profile', [UserController::class, 'changeProfile']);

    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::get('quotations/enum-options', [QuotationController::class, 'enumQuotationOptions']);
    Route::put('/quotations/{quotation}/reassign-specialist', [QuotationController::class, 'reassignSpecialist']);
    Route::put('/quotations/{quotation}/accept', [QuotationController::class, 'acceptQuotation']);
    Route::apiResource('quotations', QuotationController::class)->except(['update']);
    Route::post('/quotations/{quotation}', [QuotationController::class, 'update']);
    Route::get('/quotations/{quotation}/client-inputs', [QuotationController::class, 'clientInputs']);

    Route::apiResource('quotations.issued-quotations', IssuedQuotationController::class)
        ->except(['index', 'update'])
        ->scoped()
        ->parameters(['issued-quotations' => 'issuedQuotation']);
    Route::post('/quotations/{quotation}/issued-quotations/{issuedQuotation}', [IssuedQuotationController::class, 'update']);
    
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

    // Job Order Routes
    Route::get('/job-orders/enums', [JobOrderController::class, 'jobOrderEnums']);
    Route::get('/job-orders/{job_order}/quotation', [JobOrderController::class, 'showJobOrderQuotation']);
    Route::put('/job-orders/{job_order}/accept', [JobOrderController::class, 'acceptJobOrder']);
    Route::apiResource('job-orders', JobOrderController::class)->only(['store', 'show', 'index']);
    
    // Configuration Template Routes
    Route::prefix('configs')->group(function() {
        Route::apiResource('billing', BillingConfigController::class)
            ->parameters(['billing' => 'record']);;
        Route::apiResource('details', DetailsConfigController::class)
            ->parameters(['details' => 'record']);;
        Route::apiResource('standard-templates', StandardConfigurationController::class)
            ->parameters(['standard-templates' => 'template']);
    });

    Route::apiResource('message-templates', MessageTemplateController::class)
        ->parameters(['message-templates' => 'message']);

    Route::apiResource('templates', QuotationTemplateController::class);

    Route::get('quotation-fields', QuotationFieldController::class);

    // Role Routes
    Route::get('/roles', [RoleController::class, 'index']);
});
