<?php

use Illuminate\Support\Facades\Route;
use Modules\SocialConnection\Http\Controllers\SocialConnectionController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('socialconnections', SocialConnectionController::class)->names('socialconnection');
});
