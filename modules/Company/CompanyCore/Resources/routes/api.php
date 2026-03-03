<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\CompanyCore\Controllers\CompanyController;
use Modules\RoleAndPermission\Enums\Permission;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

Route::get('/company-by-host', [CompanyController::class, 'getCompanyByHost'])->name('companies.company-by-host');

Route::middleware(['auth:api'])->group(function () {
    Route::get('branches/user/{id}', [CompanyController::class, 'branches']);
    Route::get('managements/user/{id}', [CompanyController::class, 'managements']);
});

Route::middleware(['auth:api', InitializeTenancyByRequestData::class])->group(function () {
    Route::get('/', [CompanyController::class, 'index'])->name('companies.index')->permission(Permission::COMPANY_LIST());
    Route::get('/clients', [CompanyController::class, 'getClientCompanies'])->name('companies.clients')->permission(Permission::COMPANY_LIST());
    Route::get('/brokers', [CompanyController::class, 'getBrokerCompanies'])->name('companies.brokers')->permission(Permission::COMPANY_LIST());
    Route::get('/current-auth-company', [CompanyController::class, 'getCurrentCompanyLoggedIn'])->name('companies.current-auth-company');
    Route::post('/export', [CompanyController::class, 'export'])->name('companies.export')->permission(Permission::COMPANY_EXPORT());
    Route::get('/widget', [CompanyController::class, 'widget']);
    Route::post('/', [CompanyController::class, 'store'])->name('companies.store');//TODO ->permission(Permission::COMPANY_CREATE(),Permission::CLIENT_CREATE());
    Route::post('/validated', [CompanyController::class, 'validated']);
    Route::post('/test', [CompanyController::class, 'test']);

    Route::put('/{id}/activate', [CompanyController::class, 'activate']);
    Route::get('/{id}', [CompanyController::class, 'show'])->name('companies.show')->permission(Permission::COMPANY_VIEW(),Permission::COMPANY_VIEW());
    Route::put('/{id}', [CompanyController::class, 'update'])->permission(Permission::COMPANY_UPDATE());
    Route::delete('/delete-last', [CompanyController::class, 'deleteLastCreated'])->name('companies.delete')->permission(Permission::COMPANY_DELETE());
    Route::delete('/{id}', [CompanyController::class, 'delete'])->name('companies.delete')->permission(Permission::COMPANY_DELETE());

    Route::group(['prefix' => 'company-profile'], function () {
        Route::prefix("official-data")->group(function () {
            Route::put("/", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "updateOfficialData"]);//->permission(Permission::COMPANY_PROFILE_OFFICIAL_DATA_UPDATE());
            Route::post("/request", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "updateOfficialDataRequest"]);//->permission(Permission::COMPANY_PROFILE_OFFICIAL_DATA_UPDATE());
        });

        Route::prefix("official-document")->group(function () {
            Route::post("/", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "createOfficialDocument"]);//->permission(Permission::COMPANY_PROFILE_OFFICIAL_DOCUMENT_CREATE());
            Route::post("/update/{id}", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "updateOfficialDocument"]);//->permission(Permission::COMPANY_PROFILE_OFFICIAL_DOCUMENT_UPDATE());
            Route::delete("/media/{id}/{media_id}", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "deleteOfficialDocumentMedia"]);
            Route::delete("/{id}", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "deleteOfficialDocument"]);//->permission(Permission::COMPANY_PROFILE_OFFICIAL_DOCUMENT_DELETE());
        });

        Route::prefix("legal-data")->group(function () {
            Route::post("/request", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "requestUpdateLegalDataRequest"])->permission(Permission::COMPANY_PROFILE_LEGAL_DATA_UPDATE() , Permission::COMPANY_PROFILE_BRANCH_VIEW() );
            Route::post("/update", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "updateCompanyLegalData"])->permission(Permission::COMPANY_PROFILE_LEGAL_DATA_UPDATE() , Permission::COMPANY_PROFILE_BRANCH_VIEW() );
            Route::post("/create-legal-data", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "createLegalData"])->permission(Permission::COMPANY_PROFILE_LEGAL_DATA_CREATE() , Permission::COMPANY_PROFILE_BRANCH_VIEW() );
            Route::delete("/{id}", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "deleteLegalData"])->permission(Permission::COMPANY_PROFILE_LEGAL_DATA_DELETE() , Permission::COMPANY_PROFILE_BRANCH_VIEW() );
        });

        Route::post("assign-logo", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "setCompanyLogo"]);
        Route::post("validate-logo", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "validateCompanyLogo"]);

        Route::prefix("national-address")->group(function () {
            Route::post("/", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "getAddressFromMap"])->permission(Permission::COMPANY_VIEW(),Permission::COMPANY_PROFILE_ADDRESS_VIEW() , Permission::COMPANY_PROFILE_BRANCH_VIEW() );
            Route::put("/{id}", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "setAddress"])->permission(Permission::COMPANY_PROFILE_ADDRESS_UPDATE() , Permission::COMPANY_PROFILE_BRANCH_VIEW() );
        });

        // Separated API endpoints for company data
        Route::get("company-legal-data", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "getCompanyLegalData"])->permission(Permission::COMPANY_VIEW(),Permission::COMPANY_PROFILE_LEGAL_DATA_VIEW() , Permission::COMPANY_PROFILE_BRANCH_VIEW() );
        Route::get("company-address", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "getCompanyAddress"])->permission(Permission::COMPANY_VIEW(),Permission::COMPANY_PROFILE_ADDRESS_VIEW() , Permission::COMPANY_PROFILE_BRANCH_VIEW() );
        Route::get("company-official-documents", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "getCompanyOfficialDocuments"])->permission(Permission::COMPANY_VIEW(),Permission::COMPANY_PROFILE_OFFICIAL_DOCUMENT_VIEW() , Permission::COMPANY_PROFILE_BRANCH_VIEW() );
        Route::get("company-branches", [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "getCompanyBranches"])->permission(Permission::COMPANY_VIEW(),Permission::COMPANY_PROFILE_BRANCH_LIST() , Permission::COMPANY_PROFILE_BRANCH_VIEW() );

    });
});
