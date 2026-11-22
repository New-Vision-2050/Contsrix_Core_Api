<?php

use Illuminate\Support\Facades\Route;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\Forum\Controllers\ForumAnnouncementController;
use Modules\Forum\Controllers\ForumCatalogController;
use Modules\Forum\Controllers\ForumController;
use Modules\Forum\Controllers\ForumGroupController;
use Modules\Forum\Controllers\ForumMessageController;
use Modules\Forum\Controllers\ForumReportController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/overview', [ForumController::class, 'overview']);
    Route::get('/access', [ForumController::class, 'access']);
    Route::get('/employees', [ForumController::class, 'employees'])->permission(
        Permission::FORUM_CREATE(),
        Permission::FORUM_GROUP_CREATE(),
        Permission::FORUM_MEMBER_UPDATE(),
        Permission::FORUM_ADMIN_UPDATE(),
    );
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

    Route::get('/forums', [ForumCatalogController::class, 'index']);
    Route::post('/forums', [ForumCatalogController::class, 'store'])->permission(Permission::FORUM_CREATE());
    Route::get('/forums/{forumId}', [ForumCatalogController::class, 'show'])->permission(Permission::FORUM_VIEW());
    Route::post('/forums/{forumId}', [ForumCatalogController::class, 'update'])->permission(Permission::FORUM_UPDATE());
    Route::post('/forums/{forumId}/archive', [ForumCatalogController::class, 'archive'])->permission(Permission::FORUM_ARCHIVE());
    Route::post('/forums/{forumId}/restore', [ForumCatalogController::class, 'restore'])->permission(Permission::FORUM_RESTORE());
    Route::get('/forums/{forumId}/overview', [ForumCatalogController::class, 'overview']);
    Route::get('/forums/{forumId}/access', [ForumCatalogController::class, 'access']);
    Route::get('/forums/{forumId}/admins', [ForumCatalogController::class, 'admins'])->permission(Permission::FORUM_ADMIN_LIST());
    Route::put('/forums/{forumId}/admins', [ForumCatalogController::class, 'syncAdmins'])->permission(Permission::FORUM_ADMIN_UPDATE());
    Route::get('/forums/{forumId}/members', [ForumCatalogController::class, 'members'])->permission(Permission::FORUM_MEMBER_LIST());
    Route::put('/forums/{forumId}/members', [ForumCatalogController::class, 'syncMembers'])->permission(Permission::FORUM_MEMBER_UPDATE());
    Route::put('/forums/{forumId}/members/{userId}/groups', [ForumCatalogController::class, 'distribute'])->permission(Permission::FORUM_MEMBER_UPDATE());
    Route::get('/forums/{forumId}/activity', [ForumCatalogController::class, 'activity'])->permission(Permission::FORUM_ACTIVITY_LIST());
    Route::get('/forums/{forumId}/reports', [ForumReportController::class, 'index'])->permission(Permission::FORUM_REPORT_LIST());
    Route::post('/forums/{forumId}/reports/{reportId}/resolve', [ForumReportController::class, 'resolve'])->permission(Permission::FORUM_REPORT_UPDATE());

    Route::get('/forums/{forumId}/announcements', [ForumAnnouncementController::class, 'index']);
    Route::post('/forums/{forumId}/announcements/read', [ForumAnnouncementController::class, 'markRead']);
    Route::post('/forums/{forumId}/announcements', [ForumAnnouncementController::class, 'store'])->permission(Permission::FORUM_ANNOUNCEMENT_CREATE());
    Route::post('/forums/{forumId}/announcements/{id}', [ForumAnnouncementController::class, 'update'])->permission(Permission::FORUM_ANNOUNCEMENT_UPDATE());
    Route::delete('/forums/{forumId}/announcements/{id}', [ForumAnnouncementController::class, 'destroy'])->permission(Permission::FORUM_ANNOUNCEMENT_DELETE());

    Route::get('/forums/{forumId}/groups', [ForumGroupController::class, 'index'])->permission(Permission::FORUM_GROUP_LIST());
    Route::post('/forums/{forumId}/groups', [ForumGroupController::class, 'store'])->permission(Permission::FORUM_GROUP_CREATE());
    Route::get('/forums/{forumId}/groups/{id}', [ForumGroupController::class, 'show'])->permission(Permission::FORUM_GROUP_VIEW());
    Route::post('/forums/{forumId}/groups/{id}', [ForumGroupController::class, 'update'])->permission(Permission::FORUM_GROUP_UPDATE());
    Route::put('/forums/{forumId}/groups/{id}', [ForumGroupController::class, 'update'])->permission(Permission::FORUM_GROUP_UPDATE());
    Route::delete('/forums/{forumId}/groups/{id}', [ForumGroupController::class, 'destroy'])->permission(Permission::FORUM_GROUP_DELETE());
    Route::post('/forums/{forumId}/groups/{id}/archive', [ForumGroupController::class, 'archive'])->permission(Permission::FORUM_GROUP_ARCHIVE());
    Route::post('/forums/{forumId}/groups/{id}/restore', [ForumGroupController::class, 'restore'])->permission(Permission::FORUM_GROUP_RESTORE());
    Route::get('/forums/{forumId}/groups/{id}/access', [ForumGroupController::class, 'access']);
    Route::get('/forums/{forumId}/groups/{id}/members', [ForumGroupController::class, 'members'])->permission(Permission::FORUM_MEMBER_LIST());
    Route::put('/forums/{forumId}/groups/{id}/members', [ForumGroupController::class, 'syncMembers'])->permission(Permission::FORUM_MEMBER_UPDATE());
    Route::put('/forums/{forumId}/groups/{id}/send-settings', [ForumGroupController::class, 'updateSendSettings'])->permission(Permission::FORUM_GROUP_UPDATE());
    Route::post('/forums/{forumId}/groups/{id}/read', [ForumGroupController::class, 'markRead']);

    Route::get('/forums/{forumId}/groups/{groupId}/messages', [ForumMessageController::class, 'index']);
    Route::post('/forums/{forumId}/groups/{groupId}/messages', [ForumMessageController::class, 'store']);
    Route::delete('/forums/{forumId}/groups/{groupId}/messages/{messageId}', [ForumMessageController::class, 'destroy']);
    Route::post('/forums/{forumId}/groups/{groupId}/messages/{messageId}/reports', [ForumMessageController::class, 'report']);

    Route::get('/groups/{groupId}/messages', [ForumMessageController::class, 'index']);
    Route::post('/groups/{groupId}/messages', [ForumMessageController::class, 'store']);
    Route::delete('/groups/{groupId}/messages/{messageId}', [ForumMessageController::class, 'destroy']);
});
