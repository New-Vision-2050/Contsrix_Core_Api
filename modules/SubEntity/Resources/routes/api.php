<?php

use Illuminate\Support\Facades\Route;
use Modules\SubEntity\Controllers\RegistrationFormController;
use Modules\SubEntity\Controllers\SubEntityController;
use Modules\SubEntity\Controllers\SuperEntityController;
use Modules\SubEntity\Controllers\SubEntityRecordsController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [SubEntityController::class, 'index']);
    Route::post('/', [SubEntityController::class, 'store']);
    Route::get('/{id}', [SubEntityController::class, 'show']);
    Route::put('/{id}', [SubEntityController::class, 'update']);
    Route::delete('/{id}', [SubEntityController::class, 'delete']);
    Route::get('/{id}/show/attributes', [SubEntityController::class, 'showAttributes']);
    Route::put('/{id}/update/attributes', [SubEntityController::class, 'updateAttributes']);
    Route::get('/super_entity/sub_tables', [SubEntityController::class, 'getBySuperEntity']);
    Route::get('/list/selection', [SubEntityController::class, 'getSelection']);
    Route::put('/{id}/status', [SubEntityController::class, 'updateStatus']);


    // super entity
    Route::get('/super_entities/list', [SuperEntityController::class, 'index']);
    Route::get('/super_entities/attributes', [SuperEntityController::class, 'getAvailableAttributes']);
    Route::get('/super_entities/registration_forms', [SuperEntityController::class, 'getRegistrationForms']);
    Route::get('/registration_forms/selection/list', [RegistrationFormController::class, 'getRegistrationForms']);
    Route::get('/super_entities/allowed_attributes/config', [SuperEntityController::class, 'getAttributesConfig']);
    Route::post('/super_entities/allowed_attributes/config/{id}', [SuperEntityController::class, 'setAttributesConfig']);
    Route::post('/super_entities/registration/config', [SuperEntityController::class, 'setRegistrationConfig']);
    Route::get('/super_entities/registration/config', [SuperEntityController::class, 'getRegistrationConfig']);


    // sub-entity records
    Route::get('/records/list', [SubEntityRecordsController::class, 'index']);
});
