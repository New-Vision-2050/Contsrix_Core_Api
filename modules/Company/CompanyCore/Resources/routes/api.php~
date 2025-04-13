<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\CompanyCore\Controllers\CompanyController;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

Route::get('/company-by-host', [CompanyController::class, 'getCompanyByHost'])->name('companies.company-by-host');

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function () {
    Route::get('/', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/current-auth-company', [CompanyController::class, 'getCurrentCompanyLoggedIn'])->name('companies.current-auth-company');
    Route::get('/widget', [CompanyController::class, 'widget']);
    Route::post('/', [CompanyController::class, 'store'])->name('companies.store');
    Route::post('/validated', [CompanyController::class, 'validated']);
    Route::post('/test', [CompanyController::class, 'test']);

    Route::put('/{id}/activate', [CompanyController::class, 'activate']);
    Route::get('/{id}', [CompanyController::class, 'show'])->name('companies.show');
    Route::put('/{id}', [CompanyController::class, 'update']);
    Route::delete('/{id}', [CompanyController::class, 'delete'])->name('companies.delete');
    Route::group(['prefix' => 'company-profile', 'middleware' => [\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
        Route::prefix("official-data")->group(function () {
            Route::put("/", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "updateOfficialData"]);
            Route::put("/request", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "updateOfficialDataRequest"]);
        });

        Route::prefix("legal-data")->group(function () {
            Route::post("/request", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "updateLegalDataRequest"]);
            Route::post("/update", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "updateCompanyLegalData"]);
            Route::post("/create-legal-data", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "createLegalData"]);
        });

        Route::post("assign-logo", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "setCompanyLogo"]);
        Route::post("validate-logo", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "validateCompanyLogo"]);

        Route::prefix("national-address")->group(function () {
            Route::post("/", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "getAddressFromMap"]);
        });

    });
});
