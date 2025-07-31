<?php

use Illuminate\Support\Facades\Route;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\SubEntity\Controllers\RegistrationFormController;
use Modules\SubEntity\Controllers\SubEntityController;
use Modules\SubEntity\Controllers\SuperEntityController;
use Modules\SubEntity\Controllers\SubEntityRecordsController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [SubEntityController::class, 'index'])->permission(Permission::SUB_ENTITY_LIST());
    Route::post('/', [SubEntityController::class, 'store'])->permission(Permission::SUB_ENTITY_CREATE());
    Route::post('/slug-validate', [SubEntityController::class, 'validateSlug']);
    Route::get('/{id}', [SubEntityController::class, 'show']);
    Route::put('/{id}', [SubEntityController::class, 'update'])->permission(Permission::SUB_ENTITY_UPDATE());
    Route::delete('/{id}', [SubEntityController::class, 'delete'])->permission(Permission::SUB_ENTITY_DELETE());
    Route::get('/{id}/show/attributes', [SubEntityController::class, 'showAttributes']);
    Route::put('/{id}/update/attributes', [SubEntityController::class, 'updateAttributes']);
    Route::get('/super_entity/sub_tables', [SubEntityController::class, 'getBySuperEntity']);
    Route::post('/super_entity/sub_tables/export', [SubEntityController::class, 'export'])->permission(Permission::SUB_ENTITY_EXPORT());
    Route::get('/list/selection', [SubEntityController::class, 'getSelection']);
    Route::put('/{id}/status', [SubEntityController::class, 'updateStatus'])->permission(Permission::SUB_ENTITY_ACTIVATE());


    // super entity
    Route::get('/super_entities/list', [SuperEntityController::class, 'index'])->permission(Permission::SUPER_ENTITY_LIST());
    Route::get('/super_entities/default_attributes', [SuperEntityController::class, 'getDefaultAttributes']);
    Route::get('/super_entities/optional_attributes', [SuperEntityController::class, 'getOptionalAttributes']);
    Route::get('/super_entities/attributes/all', [SuperEntityController::class, 'getAllAttributesForSelection']);
    Route::get('/super_entities/registration_forms', [SuperEntityController::class, 'getRegistrationForms']);
    Route::get('/registration_forms/selection/list', [RegistrationFormController::class, 'getRegistrationForms']);
    Route::get('/super_entities/attributes/config', [SuperEntityController::class, 'getAttributesConfig']);
    Route::post('/super_entities/attributes/config/{id}', [SuperEntityController::class, 'setAttributesConfig']);
    Route::post('/super_entities/registration/config', [SuperEntityController::class, 'setRegistrationConfig']);
    Route::get('/super_entities/registration/config', [SuperEntityController::class, 'getRegistrationConfig'])->permission(Permission::SUPER_ENTITY_VIEW());


    // sub-entity records
    Route::get('/records/list', [SubEntityRecordsController::class, 'index']);
});
