<?php

use Illuminate\Support\Facades\Route;
use Modules\Forum\Controllers\ForumAnnouncementController;
use Modules\Forum\Controllers\ForumController;
use Modules\Forum\Controllers\ForumGroupController;
use Modules\Forum\Controllers\ForumMessageController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/overview', [ForumController::class, 'overview']);
    Route::get('/access', [ForumController::class, 'access']);
    Route::get('/employees', [ForumController::class, 'employees']);
    Route::post('/broadcasting/auth', [ForumController::class, 'broadcastingAuth']);

    Route::get('/announcements', [ForumAnnouncementController::class, 'index']);
    Route::post('/announcements', [ForumAnnouncementController::class, 'store']);
    Route::post('/announcements/read', [ForumAnnouncementController::class, 'markRead']);
    Route::delete('/announcements/{id}', [ForumAnnouncementController::class, 'destroy']);

    Route::get('/groups', [ForumGroupController::class, 'index']);
    Route::post('/groups', [ForumGroupController::class, 'store']);
    Route::get('/groups/{id}', [ForumGroupController::class, 'show']);
    Route::post('/groups/{id}', [ForumGroupController::class, 'update']);
    Route::put('/groups/{id}', [ForumGroupController::class, 'update']);
    Route::delete('/groups/{id}', [ForumGroupController::class, 'destroy']);
    Route::get('/groups/{id}/access', [ForumGroupController::class, 'access']);
    Route::get('/groups/{id}/members', [ForumGroupController::class, 'members']);
    Route::put('/groups/{id}/members', [ForumGroupController::class, 'syncMembers']);
    Route::put('/groups/{id}/send-settings', [ForumGroupController::class, 'updateSendSettings']);
    Route::post('/groups/{id}/read', [ForumGroupController::class, 'markRead']);

    Route::get('/groups/{groupId}/messages', [ForumMessageController::class, 'index']);
    Route::post('/groups/{groupId}/messages', [ForumMessageController::class, 'store']);
    Route::delete('/groups/{groupId}/messages/{messageId}', [ForumMessageController::class, 'destroy']);
});
