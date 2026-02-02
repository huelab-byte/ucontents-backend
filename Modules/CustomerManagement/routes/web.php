<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomerManagement\Http\Controllers\CustomerManagementController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('customermanagements', CustomerManagementController::class)->names('customermanagement');
});
