<?php

use Illuminate\Support\Facades\Route;
use Modules\ArchiveLibrary\File\Controllers\FileController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [FileController::class, 'index'])
       ;

    Route::get('/widgets', [FileController::class, 'getFilesWithWidgets'])
        ->permission(Permission::FOLDER_LIST());

    Route::post('/', [FileController::class, 'store'])
        ->permission(Permission::FILE_CREATE());

    Route::post('/export', [FileController::class, 'export'])
        ->name('file.export')
        ->permission(Permission::FILE_EXPORT());

    Route::post('/copy', [FileController::class, 'copyFile'])
      ;

    Route::post('/cut', [FileController::class, 'cutFile'])
        ;

    Route::post('/share', [FileController::class, 'shareFile'])
        ;

    Route::put('/{id}/change-status', [FileController::class, 'changeStatus'])
        ->permission(Permission::FILE_UPDATE());

    Route::get('/{id}', [FileController::class, 'show'])
      ;

    Route::post('/{id}', [FileController::class, 'update'])
        ->permission(Permission::FILE_UPDATE());

    Route::delete('/{id}', [FileController::class, 'delete'])
        ->permission(Permission::FILE_DELETE());
});
