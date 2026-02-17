<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\UserController;
use Modules\RoleAndPermission\Enums\Permission;

Route::post('/available-tenants-for-user', [UserController::class, 'getAvailableTenantsForUser'])->name('tenants-for-user-by-email');

Route::post('companies-by-email', [UserController::class, 'getUserCompaniesByEmail']);

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [UserController::class, 'index'])->name("users.list");
    Route::get('/get-by-role', [UserController::class, 'getByRole'])->name("users.list")->permission(Permission::USER_LIST());
    Route::get('/get-by-email-with-branches', [UserController::class, 'getUserByGlobalId'])->permission(Permission::USER_VIEW());

    Route::get('/available-tenants-for-auth-user', [UserController::class, 'getAvailableTenantsForAuthUser'])->name("tenants-for-user");
    Route::get('/admin-users', [UserController::class, 'getAdminUsers'])->name("users.admin-list")->permission(Permission::USER_LIST());
    Route::post('/', [UserController::class, 'store'])->permission(Permission::USER_CREATE());
    Route::get('/me', [UserController::class, 'me']);
    Route::get('/my-permissions', [UserController::class, 'getMyPermissions']);
    Route::get('/my-roles', [UserController::class, 'getMyRoles']);
    Route::post('/send-email-company-link', [UserController::class, 'sendEmail']);
    Route::get('/info-alert', [UserController::class, 'getInfoAlert']);

    Route::get('/{id}', [UserController::class, 'show'])->permission(Permission::USER_VIEW());
    Route::get('/{id}/roles', [UserController::class, 'getRoles'])->permission(Permission::USER_VIEW());
    Route::get('/{id}/permissions', [UserController::class, 'getPermissions'])->permission(Permission::USER_VIEW());
    Route::get('/{id}/audits', [UserController::class, 'getAudites']);
    Route::put('/{id}/update-login-way', [UserController::class, 'updateLoginWay'])->permission(Permission::USER_UPDATE());

    Route::put('/{id}', [UserController::class, 'update'])->permission(Permission::USER_UPDATE());
    Route::post('/{id}/assign-roles', [UserController::class, 'assignRolesForUser'])->permission(Permission::USER_UPDATE());
    Route::post('/change-role-status', [UserController::class, 'changeUserRoleStatus']);
    Route::delete('/{id}', [UserController::class, 'delete'])->permission(Permission::USER_DELETE());
    Route::post('/test-notification', [UserController::class, 'testNotification']);
    Route::post('/test-silent-notification', [UserController::class, 'testSilentNotification']);
    Route::post('/update-fcm-token', [UserController::class, 'updateFcmToken']);
});
