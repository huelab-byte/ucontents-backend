<?php

use Illuminate\Support\Facades\Route;
use Modules\AiIntegration\Http\Controllers\AiIntegrationController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('aiintegrations', AiIntegrationController::class)->names('aiintegration');
});
