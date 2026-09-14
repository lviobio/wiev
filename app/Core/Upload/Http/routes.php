<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Core\Upload\Http\Controllers as C;

// @generated-routes:start UploadController
Route::group(['prefix' => 'uploads', 'as' => 'uploads.', 'controller' => C\UploadController::class], function () {
    Route::post('/', 'store')->name('store');
    Route::post('chunked/{chunkedUpload}/complete', 'completeChunked')->name('chunked.complete');
});
// @generated-routes:end UploadController

// @generated-routes:start ChunkedUploadController
Route::group(['prefix' => 'uploads/chunked', 'as' => 'uploads.chunked.', 'controller' => C\ChunkedUploadController::class], function () {
    Route::post('/', 'store')->name('store');
    Route::group(['prefix' => '{chunkedUpload}'], function () {
        Route::post('chunks', 'appendChunk')->name('chunks.store');
        Route::get('/', 'show')->name('show');
        Route::delete('/', 'destroy')->name('destroy');
    });
});
// @generated-routes:end ChunkedUploadController
