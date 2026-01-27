<?php

use Illuminate\Support\Facades\Route;
use Modules\AudioLibrary\Http\Controllers\AudioLibraryController;

Route::middleware(['web'])->group(function () {
    Route::resource('audiolibrary', AudioLibraryController::class)->names('audiolibrary');
});
