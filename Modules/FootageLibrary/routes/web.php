<?php

use Illuminate\Support\Facades\Route;
use Modules\FootageLibrary\Http\Controllers\FootageLibraryController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('footagelibraries', FootageLibraryController::class)->names('footagelibrary');
});
