<?php

use Illuminate\Support\Facades\Route;
use Modules\ArchiveLibrary\Folder\Controllers\FolderController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [FolderController::class, 'showFolders']);
    Route::post('/', [FolderController::class, 'store']);
    Route::get('/child-folders/{id}', [FolderController::class, 'getChildFolders']);
    Route::post('/file', [FolderController::class, 'file']);
    Route::get('/{id}', [FolderController::class, 'show']);
    Route::put('/{id}', [FolderController::class, 'update']);
    Route::delete('/{id}', [FolderController::class, 'delete']);
});
