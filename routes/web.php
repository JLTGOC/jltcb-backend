<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuotationFileController;
use App\Http\Controllers\IssuedQuotationController;

Route::get('/', function () {
    return view('welcome');
});

// Routes for secured file viewing in the browser
Route::middleware(['auth:sanctum', 'signed'])->group(function () {
    Route::get('/files/{file}/view', [QuotationFileController::class, 'view'])
        ->name('files.view');

    Route::get('/signatures/{id}', [IssuedQuotationController::class, 'viewSignature'])
        ->name('signatures.view');
});