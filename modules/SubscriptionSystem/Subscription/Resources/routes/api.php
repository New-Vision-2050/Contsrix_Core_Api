<?php

use Illuminate\Support\Facades\Route;
<<<<<<< HEAD:modules/Subscription/Resources/routes/api.php
use Modules\Subscription\Controllers\SubscriptionController;
=======
use Modules\SubscriptionSystem\Subscription\Controllers\FeatureController;
use Modules\SubscriptionSystem\Subscription\Controllers\SubscriptionController;
>>>>>>> 955f685f (merge with subscription and solve conflicts):modules/SubscriptionSystem/Subscription/Resources/routes/api.php

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [SubscriptionController::class, 'index']);
    Route::post('/', [SubscriptionController::class, 'store']);
    Route::get('/{id}', [SubscriptionController::class, 'show']);
    Route::put('/{id}', [SubscriptionController::class, 'update']);
    Route::delete('/{id}', [SubscriptionController::class, 'delete']);
<<<<<<< HEAD:modules/Subscription/Resources/routes/api.php
=======

>>>>>>> 955f685f (merge with subscription and solve conflicts):modules/SubscriptionSystem/Subscription/Resources/routes/api.php
});
