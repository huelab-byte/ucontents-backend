<?php

use Illuminate\Support\Facades\Route;
use Modules\VideoOverlay\Http\Controllers\VideoOverlayController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('videooverlays', VideoOverlayController::class)->names('videooverlay');
});
