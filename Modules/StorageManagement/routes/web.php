<?php

use Illuminate\Support\Facades\Route;
use Modules\StorageManagement\Http\Controllers\StorageManagementController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('storagemanagements', StorageManagementController::class)->names('storagemanagement');
});
