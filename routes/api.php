<?php
declare(strict_types=1);

use App\Http\Controllers as C;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'posts', 'as' => 'posts.', 'controller' => C\PostController::class], function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::group(['prefix' => '{post}'], function () {
        Route::get('/', 'show')->name('show');
        Route::put('/', 'update')->name('update');
        Route::delete('/', 'destroy')->name('destroy');
    });
});
