<?php

use Illuminate\Support\Facades\Route;
use Modules\PageBuilder\Controllers\SchemaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('schema')->group(function () {
    Route::get('/', [SchemaController::class, 'index'])
        ->name('schema.index');
    
    Route::get('/{tableName}', [SchemaController::class, 'show'])
        ->name('schema.show');
});