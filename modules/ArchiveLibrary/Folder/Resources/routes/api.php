<?php

use Illuminate\Support\Facades\Route;
use Modules\ArchiveLibrary\Folder\Controllers\FolderController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [FolderController::class, 'showFolders']);
//        ->permission(Permission::FOLDER_LIST());

    Route::get('/get-all-folders', [FolderController::class, 'index']);
//        ->permission(Permission::FOLDER_LIST());

    Route::get('/contents', [FolderController::class, 'getFoldersAndFiles']);
//        ->permission(Permission::FOLDER_LIST());

    Route::post('/', [FolderController::class, 'store']);
//        ->permission(Permission::FOLDER_CREATE());

    Route::get('/child-folders/{id}', [FolderController::class, 'getChildFolders']);
//        ->permission(Permission::FOLDER_LIST());

    Route::post('/file', [FolderController::class, 'file']);
//        ->permission(Permission::FOLDER_ADD_FILE());

    Route::get('/{id}/users', [FolderController::class, 'getUsersAllowedByFolderId'])
        ->permission(Permission::FOLDER_LIST());

    Route::get('/{id}/audits', [FolderController::class, 'getFolderAudits'])
        ->permission(Permission::FOLDER_LIST());

    Route::get('/{id}', [FolderController::class, 'show'])
        ->permission(Permission::FOLDER_LIST());

    Route::post('/{id}', [FolderController::class, 'update'])
        ->permission(Permission::FOLDER_UPDATE());

    Route::delete('/{id}', [FolderController::class, 'delete'])
        ->permission(Permission::FOLDER_DELETE());
});
