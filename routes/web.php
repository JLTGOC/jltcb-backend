<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\IssuedQuotationController;
use App\Http\Controllers\QuotationFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Routes for secured file viewing in the browser
Route::middleware('auth:sanctum')->group(function () {

    Route::middleware('signed')->group(function() {
        Route::get('/files/{file}/view', [QuotationFileController::class, 'view'])
        ->name('files.view');

        Route::get('/signatures/{id}', [IssuedQuotationController::class, 'viewSignature'])
            ->name('signatures.view');

        Ro
    });

    Route::get('/chat/attachments/{message}', [ChatController::class, 'viewAttachments'])
        ->name('chat.attachments.view');
});
