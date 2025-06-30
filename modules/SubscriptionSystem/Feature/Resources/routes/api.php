<?php

use Illuminate\Support\Facades\Route;
use Modules\SubscriptionSystem\Feature\Controllers\FeatureController;
use Illuminate\Support\Facades\Artisan;
Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [FeatureController::class, 'index']);
    Route::get('/reset-database', function () {
    Artisan::call('migrate:fresh --seed');

    return '✔️ تمت إعادة تهيئة قاعدة البيانات وإعادة ملئها بالبيانات الوهمية.';
});
    Route::post('/', [FeatureController::class, 'store']);
    Route::post('/permissions', [FeatureController::class, 'getFeaturePermissions']);

    Route::get('/{id}', [FeatureController::class, 'show']);
    Route::put('/{id}', [FeatureController::class, 'update']);
    Route::delete('/{id}', [FeatureController::class, 'delete']);



});
