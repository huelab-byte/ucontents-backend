<?php

use Illuminate\Support\Facades\Route;
use Modules\PlanManagement\Http\Controllers\PlanManagementController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('planmanagements', PlanManagementController::class)->names('planmanagement');
});
