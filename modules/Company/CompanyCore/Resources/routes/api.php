<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\CompanyCore\Controllers\CompanyController;
use Modules\RoleAndPermission\Enums\Permission;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

// Public endpoints (no authentication required)
Route::get('/company-by-host', [CompanyController::class, 'getCompanyByHost'])
    ->name('companies.company-by-host');

// Authenticated endpoints (basic auth required)
Route::middleware(['auth:api'])->group(function () {
    Route::get('branches/user/{id}', [CompanyController::class, 'branches']);
    Route::get('managements/user/{id}', [CompanyController::class, 'managements']);
});

// Tenant-aware authenticated endpoints with permissions
Route::middleware(['auth:api', InitializeTenancyByRequestData::class, 'advanced.permission'])->group(function () {
    
    // Company Management Routes
    Route::prefix('companies')->name('companies.')->group(function () {
        // Basic CRUD operations
        Route::get('/', [CompanyController::class, 'index'])
            ->name('index')
            ->permission(Permission::COMPANY_LIST());
            
        Route::get('/current-auth-company', [CompanyController::class, 'getCurrentCompanyLoggedIn'])
            ->name('current-auth-company');
            
        Route::get('/{id}', [CompanyController::class, 'show'])
            ->name('show')
            ->permission(Permission::COMPANY_VIEW(), 'OR', Permission::COMPANY_PROFILE_VIEW());
            
        Route::post('/', [CompanyController::class, 'store'])
            ->name('store')
            ->permission(Permission::COMPANY_CREATE());
            
        Route::put('/{id}', [CompanyController::class, 'update'])
            ->name('update')
            ->permission(Permission::COMPANY_EDIT());
            
        Route::delete('/{id}', [CompanyController::class, 'delete'])
            ->name('delete')
            ->permission(Permission::COMPANY_DELETE());
            
        // Bulk operations (require additional confirmation)
        Route::delete('/delete-last', [CompanyController::class, 'deleteLastCreated'])
            ->name('delete-last')
            ->permission(Permission::COMPANY_DELETE());
            
        // Administrative operations
        Route::put('/{id}/activate', [CompanyController::class, 'activate'])
            ->name('activate')
            ->permission(Permission::COMPANY_ACTIVATE());
            
        // Import/Export operations
        Route::post('/export', [CompanyController::class, 'export'])
            ->name('export')
            ->permission(Permission::COMPANY_EXPORT());
            
        Route::post('/import', [CompanyController::class, 'import'])
            ->name('import')
            ->permission(Permission::COMPANY_IMPORT());
            
        // Widget and utility endpoints
        Route::get('/widget', [CompanyController::class, 'widget'])
            ->name('widget')
            ->permission(Permission::COMPANY_VIEW());
            
        // Development/Testing endpoints (conditional)
        Route::when(config('app.env') !== 'production', function () {
            Route::post('/validated', [CompanyController::class, 'validated'])
                ->name('validated');
            Route::post('/test', [CompanyController::class, 'test'])
                ->name('test');
        });
    });

    // Company Profile Management Routes
    Route::prefix('company-profile')->name('company-profile.')->group(function () {
        
        // Official Data Management
        Route::prefix('official-data')->name('official-data.')->group(function () {
            Route::get('/', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "getOfficialData"])
                ->name('index')
                ->permission(Permission::COMPANY_PROFILE_OFFICIAL_DATA_VIEW());
                
            Route::put('/', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "updateOfficialData"])
                ->name('update')
                ->permission(Permission::COMPANY_PROFILE_OFFICIAL_DATA_UPDATE());
                
            Route::post('/request', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "updateOfficialDataRequest"])
                ->name('request-update')
                ->permission(Permission::COMPANY_PROFILE_OFFICIAL_DATA_REQUEST_UPDATE());
        });

        // Official Document Management
        Route::prefix('official-document')->name('official-document.')->group(function () {
            Route::get('/', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "listOfficialDocuments"])
                ->name('index')
                ->permission(Permission::COMPANY_PROFILE_OFFICIAL_DOCUMENT_LIST());
                
            Route::get('/{id}', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "showOfficialDocument"])
                ->name('show')
                ->permission(Permission::COMPANY_PROFILE_OFFICIAL_DOCUMENT_VIEW());
                
            Route::post('/', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "createOfficialDocument"])
                ->name('create')
                ->permission(Permission::COMPANY_PROFILE_OFFICIAL_DOCUMENT_CREATE());
                
            Route::post('/update/{id}', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "updateOfficialDocument"])
                ->name('update')
                ->permission(Permission::COMPANY_PROFILE_OFFICIAL_DOCUMENT_UPDATE());
                
            Route::delete('/{id}', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "deleteOfficialDocument"])
                ->name('delete')
                ->permission(Permission::COMPANY_PROFILE_OFFICIAL_DOCUMENT_DELETE());
                
            Route::delete('/media/{id}/{media_id}', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "deleteOfficialDocumentMedia"])
                ->name('delete-media')
                ->permission(Permission::COMPANY_PROFILE_OFFICIAL_DOCUMENT_UPDATE());
        });

        // Legal Data Management
        Route::prefix('legal-data')->name('legal-data.')->group(function () {
            Route::get('/', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "listLegalData"])
                ->name('index')
                ->permission(Permission::COMPANY_PROFILE_LEGAL_DATA_LIST());
                
            Route::get('/{id}', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "showLegalData"])
                ->name('show')
                ->permission(Permission::COMPANY_PROFILE_LEGAL_DATA_VIEW());
                
            Route::post('/create-legal-data', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "createLegalData"])
                ->name('create')
                ->permission(Permission::COMPANY_PROFILE_LEGAL_DATA_CREATE());
                
            Route::post('/update', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "updateCompanyLegalData"])
                ->name('update')
                ->permission(Permission::COMPANY_PROFILE_LEGAL_DATA_UPDATE());
                
            Route::delete('/{id}', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "deleteLegalData"])
                ->name('delete')
                ->permission(Permission::COMPANY_PROFILE_LEGAL_DATA_DELETE());
                
            Route::post('/request', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "requestUpdateLegalDataRequest"])
                ->name('request-update')
                ->permission(Permission::COMPANY_PROFILE_LEGAL_DATA_REQUEST_UPDATE());
        });

        // Address Management
        Route::prefix('address')->name('address.')->group(function () {
            Route::get('/', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "getAddress"])
                ->name('index')
                ->permission(Permission::COMPANY_PROFILE_ADDRESS_VIEW());
                
            Route::put('/', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "updateAddress"])
                ->name('update')
                ->permission(Permission::COMPANY_PROFILE_ADDRESS_UPDATE());
                
            Route::post('/request', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "requestAddressUpdate"])
                ->name('request-update')
                ->permission(Permission::COMPANY_PROFILE_ADDRESS_REQUEST_UPDATE());
        });

        // Branch Information
        Route::prefix('branches')->name('branches.')->group(function () {
            Route::get('/', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "listBranches"])
                ->name('index')
                ->permission(Permission::COMPANY_PROFILE_BRANCH_LIST());
                
            Route::get('/{id}', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "showBranch"])
                ->name('show')
                ->permission(Permission::COMPANY_PROFILE_BRANCH_VIEW());
        });

        // Company Logo and Branding
        Route::post('/assign-logo', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "setCompanyLogo"])
            ->name('assign-logo')
            ->permission(Permission::COMPANY_PROFILE_OFFICIAL_DATA_UPDATE());
            
        Route::post('/primary-color', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "setPrimaryColor"])
            ->name('primary-color')
            ->permission(Permission::COMPANY_PROFILE_OFFICIAL_DATA_UPDATE());
            
        Route::post('/secondary-color', [\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class, "setSecondaryColor"])
            ->name('secondary-color')
            ->permission(Permission::COMPANY_PROFILE_OFFICIAL_DATA_UPDATE());
    });
});

// Route helper macro for conditional registration
Route::macro('when', function ($condition, $callback) {
    if ($condition) {
        $callback();
    }
    return $this;
});

// Route helper macro for permission-based registration
Route::macro('permission', function (...$permissions) {
    return $this->middleware(['permission:' . implode(',', $permissions)]);
});
