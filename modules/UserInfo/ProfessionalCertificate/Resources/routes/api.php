<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\ProfessionalCertificate\Controllers\ProfessionalCertificateController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [ProfessionalCertificateController::class, 'index']);
    Route::post('/', [ProfessionalCertificateController::class, 'store']);
    Route::get('/{id}', [ProfessionalCertificateController::class, 'show']);
<<<<<<< HEAD
    Route::post('/{id}', [ProfessionalCertificateController::class, 'update']);
=======
    Route::put('/{id}', [ProfessionalCertificateController::class, 'update']);
>>>>>>> 7be6c72c (merge with stage (first version ))
    Route::delete('/{id}', [ProfessionalCertificateController::class, 'delete']);
});
