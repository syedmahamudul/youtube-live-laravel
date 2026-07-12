<?php

use App\Http\Controllers\Api\LiveClassWebhookController;
use App\Http\Controllers\LiveClassController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('live-class.index');
});

Route::prefix('live-class')->group(function () {
    Route::get('/', [LiveClassController::class, 'index'])->name('live-class.index');
    Route::get('/{id}', [LiveClassController::class, 'show'])->name('live-class.show');
    Route::get('/{id}/status', [LiveClassController::class, 'getStatus']);
    Route::get('/all-live', [LiveClassController::class, 'getAllLive']);
});

// Webhook route (for streaming server)
Route::post('/api/webhook/live-class', [LiveClassWebhookController::class, 'handle']);