<?php

use Illuminate\Support\Facades\Route;
use Modules\EmailManagement\Http\Controllers\EmailManagementController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('emailmanagements', EmailManagementController::class)->names('emailmanagement');
});
