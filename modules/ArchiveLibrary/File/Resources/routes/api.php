<?php

use Illuminate\Support\Facades\Route;
use Modules\ArchiveLibrary\File\Controllers\FileController;
use Modules\RoleAndPermission\Enums\Permission;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [FileController::class, 'index'])
       ;

    Route::get('/widgets', [FileController::class, 'getFilesWithWidgets']);
//        ->permission(Permission::FOLDER_LIST());

    Route::post('/', [FileController::class, 'store']);
//        ->permission(Permission::FILE_CREATE());

    Route::post('/export', [FileController::class, 'export'])
        ->name('file.export');
//        ->permission(Permission::FILE_EXPORT());

    Route::post('/copy', [FileController::class, 'copyFile']);

    Route::post('/cut', [FileController::class, 'cutFile']);

    Route::post('/share', [FileController::class, 'shareFile']);

    Route::put('/{id}/change-status', [FileController::class, 'changeStatus']);
//        ->permission(Permission::FILE_UPDATE());


    Route::get('/{id}/download', [FileController::class, 'downloadSingleFile']);
//        ->permission(Permission::FOLDER_LIST());

    Route::post('/download', [FileController::class, 'downloadMedia']);
//        ->permission(Permission::FOLDER_LIST());





    // File Favourites Routes
    Route::post('/favourites', [FileController::class, 'addToFavourites']);
//        ->permission(Permission::FOLDER_LIST());

    Route::delete('/favourites', [FileController::class, 'removeFromFavourites']);
//        ->permission(Permission::FOLDER_LIST());

    Route::get('/favourites', [FileController::class, 'getFavourites']);
//        ->permission(Permission::FOLDER_LIST());



    Route::delete('/{id}', [FileController::class, 'delete']);
//        ->permission(Permission::FILE_DELETE());

    Route::post('/{id}', [FileController::class, 'update']);
//        ->permission(Permission::FILE_UPDATE());
});
Route::get('/{id}', [FileController::class, 'show']);
