<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentGateway\Http\Controllers\PaymentGatewayController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('paymentgateways', PaymentGatewayController::class)->names('paymentgateway');
});
