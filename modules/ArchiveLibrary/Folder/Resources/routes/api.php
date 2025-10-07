<?php

use Illuminate\Support\Facades\Route;
use Modules\ArchiveLibrary\Folder\Controllers\FolderController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [FolderController::class, 'showFolders']);
    Route::get('/contents', [FolderController::class, 'getFoldersAndFiles']);
    Route::post('/', [FolderController::class, 'store']);
    Route::get('/child-folders/{id}', [FolderController::class, 'getChildFolders']);
    Route::post('/file', [FolderController::class, 'file']);
    Route::get('/{id}/users', [FolderController::class, 'getUsersAllowedByFolderId']);
    Route::get('/{id}/audits', [FolderController::class, 'getFolderAudits']);
    Route::get('/{id}', [FolderController::class, 'show']);
    Route::post('/{id}', [FolderController::class, 'update']);
    Route::delete('/{id}', [FolderController::class, 'delete']);
});
