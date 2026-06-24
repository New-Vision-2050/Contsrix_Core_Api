<?php

use Illuminate\Support\Facades\Route;
use Modules\EmployeeTask\Controllers\AdminEmployeeTaskController;
use Modules\EmployeeTask\Controllers\EmployeeTaskTypeController;
use Modules\EmployeeTask\Controllers\EmployeeTaskController;

/*
|--------------------------------------------------------------------------
| Employee-facing routes  (authenticated employee)
|--------------------------------------------------------------------------
*/
Route::prefix('employee-tasks')->group(function () {
    Route::get('/',              [EmployeeTaskController::class, 'index']);
    Route::get('/types',              [EmployeeTaskTypeController::class, 'index']);
    Route::get('/items',              [EmployeeTaskTypeController::class, 'getItems']);

    Route::get('/filters',       [EmployeeTaskController::class, 'filters']);
    Route::get('/pre-conditions', [EmployeeTaskController::class, 'preConditions']);
    Route::get('/in-form-conditions', [EmployeeTaskController::class, 'inFormConditions']);
    Route::post('/',             [EmployeeTaskController::class, 'store']);
    Route::get('/{id}', [EmployeeTaskController::class, 'show']);

    Route::delete('/{id}', [EmployeeTaskController::class, 'destroy']);

    Route::post('/{id}/start',         [EmployeeTaskController::class, 'start']);
    Route::post('/{id}/pause',         [EmployeeTaskController::class, 'pause']);
    Route::post('/{id}/resume',        [EmployeeTaskController::class, 'resume']);
    Route::post('/{id}/end',           [EmployeeTaskController::class, 'end']);
    Route::get('/{id}/status',         [EmployeeTaskController::class, 'liveStatus']);
    Route::post('/{id}/location-ping', [EmployeeTaskController::class, 'locationPing']);
    Route::get('/{id}/check-location', [EmployeeTaskController::class, 'checkLocation']);
    Route::get('/{id}/sessions',       [EmployeeTaskController::class, 'sessions']);

    // Task completion approval (ارسال للاعتماد)
    Route::post('/{id}/request-approval', [EmployeeTaskController::class, 'requestApproval']);

    Route::post('/{id}/extension-requests', [EmployeeTaskController::class, 'storeExtension']);
    Route::get('/{id}/extension-requests',  [EmployeeTaskController::class, 'listExtensions']);

    Route::get('/{id}/available-actions',   [EmployeeTaskController::class, 'availableActions']);
    Route::get('/{id}/procedures',          [EmployeeTaskController::class, 'procedures']);


});

/*
|--------------------------------------------------------------------------
| Admin-facing routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin/employee-tasks')->group(function () {
    Route::get('/',       [AdminEmployeeTaskController::class, 'index']);
    Route::get('/inbox',  [AdminEmployeeTaskController::class, 'inbox']);
    Route::get('/inbox-counts',  [AdminEmployeeTaskController::class, 'inboxCounts']);

    // Unified approve/reject — works for task_request, extension_request, and task_approval
    Route::patch('/{id}/approve', [AdminEmployeeTaskController::class, 'approve']);
    Route::patch('/{id}/reject',  [AdminEmployeeTaskController::class, 'reject']);
    Route::delete('/{id}',        [AdminEmployeeTaskController::class, 'destroy']);

    Route::get('/extension-requests',                              [AdminEmployeeTaskController::class, 'extensionRequests']);
    Route::patch('/extension-requests/{extensionId}/approve',     [AdminEmployeeTaskController::class, 'approveExtension']);
    Route::patch('/extension-requests/{extensionId}/reject',      [AdminEmployeeTaskController::class, 'rejectExtension']);
});
