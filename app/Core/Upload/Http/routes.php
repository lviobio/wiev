<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Core\Upload\Http\Controllers as C;

// @generated-routes:start UploadController
Route::group(['prefix' => 'uploads', 'as' => 'uploads.', 'controller' => C\UploadController::class], function () {
    Route::post('/', 'store')->name('store');
});
// @generated-routes:end UploadController
