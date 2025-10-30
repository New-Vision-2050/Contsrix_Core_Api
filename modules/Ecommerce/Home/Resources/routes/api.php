<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Home\Controllers\HomeController;

// Home page with banners, categories, and products
Route::get('/home1', [HomeController::class, 'index1']);
Route::get('/home2', [HomeController::class, 'index2']);

Route::get('/footer', [HomeController::class, 'footer']);
