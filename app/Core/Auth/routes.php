<?php
declare(strict_types=1);

use App\Core\Auth\Login\Http\Controllers as Login;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
    Route::group(['prefix' => 'login', 'controller' => Login\LoginController::class], function () {
        Route::post('/', 'login')->middleware('throttle:login')->name('login');
    });
});
