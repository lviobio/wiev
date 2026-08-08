<?php
declare(strict_types=1);

use App\Modules\Post\Http\Controllers as C;
use Illuminate\Support\Facades\Route;

// @generated-routes:start PostController
Route::group(['prefix' => 'posts', 'as' => 'posts.', 'controller' => C\PostController::class], function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::group(['prefix' => '{post}'], function () {
        Route::get('/', 'show')->name('show');
        Route::put('/', 'update')->name('update');
        Route::delete('/', 'destroy')->name('destroy');
        Route::delete('cover', 'removeCover')->name('cover.destroy');
    });
});
// @generated-routes:end PostController
