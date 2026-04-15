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

        // ============================================================
        // Archive Cycle Permissions
        // ============================================================
        'PROJECT_ARCHIVE_CYCLE_VIEW' => 'project-management.project-management*archive-cycle.view',
        'PROJECT_ARCHIVE_CYCLE_LIST' => 'project-management.project-management*archive-cycle.list',
        'PROJECT_ARCHIVE_CYCLE_CREATE' => 'project-management.project-management*archive-cycle.create',
        'PROJECT_ARCHIVE_CYCLE_UPDATE' => 'project-management.project-management*archive-cycle.update',
        'PROJECT_ARCHIVE_CYCLE_DELETE' => 'project-management.project-management*archive-cycle.delete',
    ]
];
