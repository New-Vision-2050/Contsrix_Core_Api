<?php

use Illuminate\Support\Facades\Route;
use Modules\Program\Controllers\ProgramController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [ProgramController::class, 'index']);
    Route::post('/', [ProgramController::class, 'store']);
    Route::get('/{id}', [ProgramController::class, 'show']);
    Route::put('/{id}', [ProgramController::class, 'update']);
    Route::delete('/{id}', [ProgramController::class, 'delete']);
    Route::get('/sub_entities/list', [ProgramController::class, 'listWithSubEntities']);
<<<<<<< HEAD
    Route::get('/sub_entities/select/list', [ProgramController::class, 'selectListWithSubEntities']);
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
});
