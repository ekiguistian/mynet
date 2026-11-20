<?php

use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/payments/{provider}', PaymentWebhookController::class)
    ->whereIn('provider', ['midtrans', 'xendit', 'tripay'])
    ->middleware('throttle:60,1');
