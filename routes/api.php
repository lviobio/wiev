<?php
declare(strict_types=1);

//use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1', 'as' => 'api.v1.'], function () {
    require base_path('app/Core/Auth/routes.php');
    Route::group(['middleware' => ['auth:sanctum']], function () {
        require base_path('app/Modules/Post/Http/routes.php');
    });
});
