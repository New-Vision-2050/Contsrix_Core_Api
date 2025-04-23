<?php

use Illuminate\Support\Facades\Route;
use Modules\PageBuilder\Http\Controllers\SchemaController;

Route::prefix('schema')->group(function () {
    Route::get('/', [SchemaController::class, 'index']);
    Route::post('/', [SchemaController::class, 'show']);
});
