<?php

use Illuminate\Support\Facades\Route;
use Modules\NotificationManagement\Http\Controllers\NotificationManagementController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('notificationmanagements', NotificationManagementController::class)->names('notificationmanagement');
});
