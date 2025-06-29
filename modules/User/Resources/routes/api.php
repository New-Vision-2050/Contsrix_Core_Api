<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\UserController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [UserController::class, 'index'])->name("users.list")->middleware("permission:" . Permission::USER_LIST());
    Route::get('/get-by-role', [UserController::class, 'getByRole'])->name("users.list")->middleware("permission:" . Permission::USER_LIST());
    Route::get('/get-by-email-with-branches', [UserController::class, 'getUserByGlobalId'])->middleware("permission:" . Permission::USER_VIEW());

    Route::get('/available-tenants-for-auth-user', [UserController::class, 'getAvailableTenantsForAuthUser'])->name("tenants-for-user");
    Route::get('/admin-users', [UserController::class, 'getAdminUsers'])->name("users.admin-list")->middleware("permission:" . Permission::USER_LIST());
    Route::post('/', [UserController::class, 'store'])->middleware("permission:" . Permission::USER_CREATE());
    Route::get('/me', [UserController::class, 'me']);
    Route::get('/my-permissions', [UserController::class, 'getMyPermissions']);
    Route::get('/my-roles', [UserController::class, 'getMyRoles']);

    Route::get('/{id}', [UserController::class, 'show'])->middleware("permission:" . Permission::USER_VIEW());
    Route::get('/{id}/roles', [UserController::class, 'getRoles'])->middleware("permission:" . Permission::USER_VIEW());
    Route::get('/{id}/permissions', [UserController::class, 'getPermissions'])->middleware("permission:" . Permission::USER_VIEW());
    Route::get('/{id}/audits', [UserController::class, 'getAudites']);
    Route::put('/{id}/update-login-way', [UserController::class, 'updateLoginWay'])->middleware("permission:" . Permission::USER_EDIT());

    Route::put('/{id}', [UserController::class, 'update'])->middleware("permission:" . Permission::USER_EDIT());
    Route::post('/{id}/assign-roles', [UserController::class, 'assignRolesForUser'])->middleware("permission:" . Permission::PERMISSION_ASSIGN());

    Route::delete('/{id}', [UserController::class, 'delete'])->middleware("permission:" . Permission::USER_DELETE());
});
