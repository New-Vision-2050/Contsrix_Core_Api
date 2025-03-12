<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Currency\Controllers\CurrencyController;
use Illuminate\Support\Facades\Artisan;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CurrencyController::class, 'index']);
    Route::get('/run-seeders', function () {

        Artisan::call('db:seed', [
            '--class' => \Modules\Shared\TimeZone\Database\Seeders\TimeZoneSeederTableSeeder::class
        ]);
        Artisan::call('db:seed', [
            '--class' => \Modules\Shared\Language\Database\Seeders\LanguageSeederTableSeeder::class
        ]);
        Artisan::call('db:seed', [
            '--class' => \Modules\Shared\Currency\Database\Seeders\CurrencySeederTableSeeder::class
        ]);

        return response()->json(['message' => 'Seeders ran successfully'], 200);

});
    Route::post('/', [CurrencyController::class, 'store']);
    Route::get('/{id}', [CurrencyController::class, 'show']);
    Route::put('/{id}', [CurrencyController::class, 'update']);
    Route::delete('/{id}', [CurrencyController::class, 'delete']);

});
