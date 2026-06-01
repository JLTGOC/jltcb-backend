<?php

use App\Http\Controllers\AccountSpecialistController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReelController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BillingConfigController;
use App\Http\Controllers\ClientController;
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
use App\Http\Controllers\ServiceOptionController;
use App\Http\Controllers\ReassignmentRequestController;
use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Broadcast;

require __DIR__ . '/public_routes.php';

Route::post('auth/login', [AuthController::class, 'login'])->middleware(['web', 'throttle:login']);

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::middleware(['auth:sanctum', 'throttle:api', 'session.timeout'])->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::prefix('articles')->group(function () {
        Route::post('/', [ArticleController::class, 'store']);
        Route::post('{article}', [ArticleController::class, 'update']);
        Route::delete('{article}', [ArticleController::class, 'destroy']);
    });

    Route::apiResource('reels', ReelController::class)->only(['store', 'update', 'destroy']);

    Route::prefix('clients')->group(function() {
        Route::get('/', [ClientController::class, 'index']); 
        Route::get('/summary', [ClientController::class, 'summary']);
        Route::get('/{client}', [ClientController::class, 'show']);

        Route::get('/{client}/quotations', [ClientController::class, 'listQuotations']);
        Route::get('/{client}/shipments', [ClientController::class, 'listShipments']);
        Route::get('/{client}/regulatory', [ClientController::class, 'listRegulatory']);
    });

    Route::get('account-specialists/', [AccountSpecialistController::class, 'index']);
    Route::get('account-specialists/summary', [AccountSpecialistController::class, 'summary']);

    Route::apiResource('users', UserController::class)->only(['show', 'update']);
    Route::put('/users/{user}/change-password', [UserController::class, 'changePassword'])->middleware('throttle:password-change');
    Route::post('/users/{user}/change-profile', [UserController::class, 'changeProfile']);

    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::post('/quotations/{quotation}/request-reassignment', [QuotationController::class, 'requestReassignment']);
    Route::get('quotations/enum-options', [QuotationController::class, 'enumQuotationOptions']);
    Route::put('/quotations/{quotation}/reassign-specialist', [QuotationController::class, 'reassignSpecialist']);
    Route::put('/quotations/{quotation}/accept-proposal', [QuotationController::class, 'acceptQuotationProposal']);
    Route::put('/quotations/{quotation}/accept-assignment', [QuotationController::class, 'acceptQuotationAssignment']);
    Route::apiResource('quotations', QuotationController::class)->except(['store', 'update']);
    Route::post('/quotations', [QuotationController::class, 'store'])->middleware('throttle:create-quotations');
    Route::post('/quotations/{quotation}', [QuotationController::class, 'update'])->middleware('throttle:create-quotations');
    Route::get('/quotations/{quotation}/client-inputs', [QuotationController::class, 'clientInputs']);

    Route::apiResource('quotations.issued-quotations', IssuedQuotationController::class)
        ->except(['index', 'store', 'update'])
        ->scoped()
        ->parameters(['issued-quotations' => 'issuedQuotation']);
    Route::post('/quotations/{quotation}/issued-quotations', [IssuedQuotationController::class, 'store'])->middleware('throttle:create-quotations');
    Route::post('/quotations/{quotation}/issued-quotations/{issuedQuotation}', [IssuedQuotationController::class, 'update'])->middleware('throttle:create-quotations');

    //Temporary routes for quotation files
    Route::post('/quotations/{quotation}/upload', [QuotationController::class, 'upload'])->middleware('throttle:file-upload');
    Route::apiResource('quotations.files', QuotationFileController::class)->only(['index', 'show', 'update'])->scoped();
    Route::get('/files/{file}/download', [QuotationFileController::class, 'download'])
        ->name('files.download');

    Route::prefix('conversations')->group(function () {
        Route::get('', [ChatController::class, 'index']); // Inbox
        Route::get('/{conversation}', [ChatController::class, 'show']);
        Route::get('{conversation}/messages', [ChatController::class, 'indexMessages']); // History
        Route::post('{conversation}', [ChatController::class, 'sendMessage'])->middleware('throttle:chat'); // Message a conversation
        Route::post('/{conversation}/read', [ChatController::class, 'markAsRead']); // For chat viewer's unread count update
    });

    // Quotation Chat
    Route::post('quotations/{quotation}/chat', [ChatController::class, 'chatWithQuotation'])->middleware('throttle:chat');

    // Shipment Routes
    Route::apiResource('shipments', ShipmentController::class)->only(['show', 'update', 'index']);
    Route::post('/shipments', [ShipmentController::class, 'store'])->middleware('throttle:create-quotations');

    // Job Order Routes
    Route::get('/job-orders/enums', [JobOrderController::class, 'jobOrderEnums']);
    Route::get('/job-orders/{job_order}/quotation', [JobOrderController::class, 'showJobOrderQuotation'])->name('job-orders.quotation');
    Route::put('/job-orders/{job_order}/accept', [JobOrderController::class, 'acceptJobOrder']);
    Route::post('/job-orders/{job_order}/request-reassignment', [JobOrderController::class, 'requestReassignment']);
    Route::put('/job-orders/{job_order}/reassign-ops', [JobOrderController::class, 'reassignOps']);
    Route::apiResource('job-orders', JobOrderController::class)->only(['show', 'index']);
    Route::post('/job-orders', [JobOrderController::class, 'store'])->middleware('throttle:create-quotations');
    
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
    Route::patch('templates/{template}/status', [QuotationTemplateController::class, 'toggleStatus']);

    Route::get('quotation-fields', QuotationFieldController::class);

    // Role Routes
    Route::get('/roles', [RoleController::class, 'index']);

    // Service Option Routes
    Route::apiResource('sub-services', ServiceOptionController::class)->only(['index', 'store']);
    Route::put('/sub-services/{serviceOption}', [ServiceOptionController::class, 'update']);
    Route::delete('/sub-services/{serviceOption}', [ServiceOptionController::class, 'destroy']);

    // Reassignment Request Routes
    Route::get('reassignment-requests/enums', [ReassignmentRequestController::class, 'enums']);
    Route::post('/reassignment-requests/{reassignmentRequest}/cancel', [ReassignmentRequestController::class, 'cancel']);
    Route::apiResource('reassignment-requests', ReassignmentRequestController::class)->only(['show']);

    // Company Routes
    Route::get('companies/enums', [CompanyController::class, 'enums']);
    Route::apiResource('companies', CompanyController::class)->except(['destroy']);
});
