<?php

use Illuminate\Support\Facades\Route;
use Modules\MediaUpload\Http\Controllers\MediaUploadController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('mediauploads', MediaUploadController::class)->names('mediaupload');
});
