<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'service' => 'constrix-api'
    ]);
});

// Get user companies by email - No tenant initialization required

