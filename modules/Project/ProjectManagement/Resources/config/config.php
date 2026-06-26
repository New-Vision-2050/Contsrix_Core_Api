<?php

return [
    'name' => 'ProjectManagement',

    'permissions' => [
        // ================================================================================================
        // PROJECT MANAGEMENT MODULE PERMISSIONS
        // Project-specific roles and permissions for employees within projects
        // ================================================================================================

        // ============================================================
        // Employee Management Permissions
        // ============================================================
        'PROJECT_EMPLOYEE_VIEW' => 'project-management.project-management*employee.view',
        'PROJECT_EMPLOYEE_LIST' => 'project-management.project-management*employee.list',
        'PROJECT_EMPLOYEE_CREATE' => 'project-management.project-management*employee.create',
        'PROJECT_EMPLOYEE_UPDATE' => 'project-management.project-management*employee.update',
        'PROJECT_EMPLOYEE_DELETE' => 'project-management.project-management*employee.delete',

        // ============================================================
        // Archive Library Permissions (Project-specific)
        // ============================================================
        'PROJECT_ARCHIVE_VIEW' => 'project-management.project-management*archive-library.view',
        'PROJECT_ARCHIVE_LIST' => 'project-management.project-management*archive-library.list',
        'PROJECT_ARCHIVE_CREATE' => 'project-management.project-management*archive-library.create',
        'PROJECT_ARCHIVE_UPDATE' => 'project-management.project-management*archive-library.update',
        'PROJECT_ARCHIVE_DELETE' => 'project-management.project-management*archive-library.delete',

        // ============================================================
        // Role Management Permissions (Project-specific)
        // ============================================================
        'PROJECT_ROLE_VIEW' => 'project-management.project-management*role.view',
        'PROJECT_ROLE_LIST' => 'project-management.project-management*role.list',
        'PROJECT_ROLE_CREATE' => 'project-management.project-management*role.create',
        'PROJECT_ROLE_UPDATE' => 'project-management.project-management*role.update',
        'PROJECT_ROLE_DELETE' => 'project-management.project-management*role.delete',


        'PROJECT_SHARE_VIEW' => 'project-management.project-management*project-share.view',
        'PROJECT_SHARE_LIST' => 'project-management.project-management*project-share.list',
        'PROJECT_SHARE_CREATE' => 'project-management.project-management*project-share.create',
        'PROJECT_SHARE_UPDATE' => 'project-management.project-management*project-share.update',
        'PROJECT_SHARE_DELETE' => 'project-management.project-management*project-share.delete',

        // ============================================================
        // Archive Cycle Permissions
        // ============================================================
        'PROJECT_ARCHIVE_CYCLE_VIEW' => 'project-management.project-management*archive-cycle.view',
        'PROJECT_ARCHIVE_CYCLE_LIST' => 'project-management.project-management*archive-cycle.list',
        'PROJECT_ARCHIVE_CYCLE_CREATE' => 'project-management.project-management*archive-cycle.create',
        'PROJECT_ARCHIVE_CYCLE_UPDATE' => 'project-management.project-management*archive-cycle.update',
        'PROJECT_ARCHIVE_CYCLE_DELETE' => 'project-management.project-management*archive-cycle.delete',

        // ============================================================
        // Attachment Cycle Settings Permissions
        // ============================================================
//        'PROJECT_ATTACHMENT_CYCLE_SETTINGS_VIEW' => 'project-management.project-management*attachment-cycle-settings.view',
//        'PROJECT_ATTACHMENT_CYCLE_SETTINGS_LIST' => 'project-management.project-management*attachment-cycle-settings.list',
//        'PROJECT_ATTACHMENT_CYCLE_SETTINGS_CREATE' => 'project-management.project-management*attachment-cycle-settings.create',
//        'PROJECT_ATTACHMENT_CYCLE_SETTINGS_UPDATE' => 'project-management.project-management*attachment-cycle-settings.update',
//        'PROJECT_ATTACHMENT_CYCLE_SETTINGS_DELETE' => 'project-management.project-management*attachment-cycle-settings.delete',

        // ============================================================
        // Archive Library Settings Permissions
        // ============================================================
//        'PROJECT_ARCHIVE_LIBRARY_SETTINGS_VIEW' => 'project-management.project-management*archive-library-settings.view',
//        'PROJECT_ARCHIVE_LIBRARY_SETTINGS_LIST' => 'project-management.project-management*archive-library-settings.list',
//        'PROJECT_ARCHIVE_LIBRARY_SETTINGS_CREATE' => 'project-management.project-management*archive-library-settings.create',
//        'PROJECT_ARCHIVE_LIBRARY_SETTINGS_UPDATE' => 'project-management.project-management*archive-library-settings.update',
//        'PROJECT_ARCHIVE_LIBRARY_SETTINGS_DELETE' => 'project-management.project-management*archive-library-settings.delete',
        // ============================================================
        // Project Notifications Permissions
        // ============================================================
        'PROJECT_NOTIFICATION_VIEW'   => 'project-management.project-management*notifications.view',
        'PROJECT_NOTIFICATION_LIST'   => 'project-management.project-management*notifications.list',
        'PROJECT_NOTIFICATION_CREATE' => 'project-management.project-management*notifications.create',
        'PROJECT_NOTIFICATION_UPDATE' => 'project-management.project-management*notifications.update',
        'PROJECT_NOTIFICATION_DELETE' => 'project-management.project-management*notifications.delete',
    ]
];
