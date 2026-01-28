<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Module Web Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "web" middleware group. These routes are primarily
| for Laravel's password reset functionality which requires named routes.
|
*/

// Password reset route (required by Laravel's Password facade)
// This route is not actually used since we use a custom notification,
// but Laravel requires it to exist for the Password facade to work.
// Note: Using 'password.reset.web' to avoid conflict with API route 'password.reset'
Route::get('/password/reset/{token}', function () {
    // This route is not used - the custom ResetPasswordNotification
    // builds the frontend URL directly
    abort(404);
})->name('password.reset.web');
