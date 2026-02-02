<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    ReelController,
    ArticleController,
    DashboardController,
    UserController,
    QuotationController,
    ShipmentController
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
        $route->get('/enum-options', [QuotationController::class, 'enumQuotationOptions']);
        $route->get('/', [QuotationController::class, 'index']);
        $route->post('/', [QuotationController::class, 'store']);
        $route->get('/{referenceNumber}', [QuotationController::class, 'show']);
        $route->put('/{referenceNumber}', [QuotationController::class, 'update']);
    });

    Route::group([
        'prefix' => 'shipment'
    ], function ($route) {
        $route->post('/', [ShipmentController::class, 'store']);
    });
});