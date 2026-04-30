<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Controllers\ReportController;
use Modules\Reports\Controllers\ReportLookupController;
use Modules\Reports\Controllers\ReportTemplateController;
use Modules\RoleAndPermission\Enums\Permission;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

Route::group(['middleware' => ['auth:api', InitializeTenancyByRequestData::class]], function () {

    // ----------------------------------------------------------------------
    // Wizard lookups (every dropdown / multi-select option in steps 1-5)
    // ----------------------------------------------------------------------
    Route::get('/lookups', [ReportLookupController::class, 'index']);

    // ----------------------------------------------------------------------
    // Report Templates ("save these settings as a template")
    // ----------------------------------------------------------------------
    Route::get('/templates', [ReportTemplateController::class, 'list'])
        ->permission(Permission::REPORT_TEMPLATE_LIST());
    Route::get('/templates/{id}', [ReportTemplateController::class, 'show'])
        ->permission(Permission::REPORT_TEMPLATE_VIEW());
    Route::post('/templates', [ReportTemplateController::class, 'store'])
        ->permission(Permission::REPORT_TEMPLATE_CREATE());
    Route::post('/templates/{id}', [ReportTemplateController::class, 'update'])
        ->permission(Permission::REPORT_TEMPLATE_UPDATE());
    Route::delete('/templates/{id}', [ReportTemplateController::class, 'delete'])
        ->permission(Permission::REPORT_TEMPLATE_DELETE());
    Route::post('/templates/{id}/generate', [ReportTemplateController::class, 'generate'])
        ->permission(Permission::REPORT_CREATE());

    // ----------------------------------------------------------------------
    // Generated Reports (the table on the reports page)
    // ----------------------------------------------------------------------
    Route::get('/', [ReportController::class, 'list'])
        ->permission(Permission::REPORT_LIST());
    Route::post('/', [ReportController::class, 'store'])
        ->permission(Permission::REPORT_CREATE());
    Route::get('/{id}', [ReportController::class, 'show'])
        ->permission(Permission::REPORT_VIEW());
    Route::get('/{id}/download', [ReportController::class, 'download'])
        ->permission(Permission::REPORT_DOWNLOAD());
    Route::post('/{id}/regenerate', [ReportController::class, 'regenerate'])
        ->permission(Permission::REPORT_REGENERATE());
    Route::delete('/{id}', [ReportController::class, 'delete'])
        ->permission(Permission::REPORT_DELETE());
});
