<?php

return [
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

//        // ============================================================
//        // Project Settings Permissions
//        // ============================================================
//        'PROJECT_SETTINGS_VIEW' => 'project-management.project-management*settings.view',
//        'PROJECT_SETTINGS_UPDATE' => 'project-management.project-management*settings.update',
//        'PROJECT_SETTINGS_DELETE' => 'project-management.project-management*settings.delete',

        // ============================================================
        // Role Management Permissions (Project-specific)
        // ============================================================
        'PROJECT_ROLE_VIEW' => 'project-management.project-management*role.view',
        'PROJECT_ROLE_LIST' => 'project-management.project-management*role.list',
        'PROJECT_ROLE_CREATE' => 'project-management.project-management*role.create',
        'PROJECT_ROLE_UPDATE' => 'project-management.project-management*role.update',
        'PROJECT_ROLE_DELETE' => 'project-management.project-management*role.delete',


        'PROJECT_ARCHIVE_CYCLE_VIEW' => 'project-management.project-management*role.view',
        'PROJECT_ARCHIVE_CYCLE_LIST' => 'project-management.project-management*role.list',
        'PROJECT_ARCHIVE_CYCLE_CREATE' => 'project-management.project-management*role.create',
        'PROJECT_ARCHIVE_CYCLE_UPDATE' => 'project-management.project-management*role.update',
        'PROJECT_ARCHIVE_CYCLE_DELETE' => 'project-management.project-management*role.delete',

//        // ============================================================
//        // Task Management Permissions
//        // ============================================================
//        'PROJECT_TASK_VIEW' => 'project-management.project-management*task.view',
//        'PROJECT_TASK_LIST' => 'project-management.project-management*task.list',
//        'PROJECT_TASK_CREATE' => 'project-management.project-management*task.create',
//        'PROJECT_TASK_UPDATE' => 'project-management.project-management*task.update',
//        'PROJECT_TASK_DELETE' => 'project-management.project-management*task.delete',
//        'PROJECT_TASK_ASSIGN' => 'project-management.project-management*task.assign',
//        'PROJECT_TASK_COMPLETE' => 'project-management.project-management*task.complete',

        // ============================================================
        // Financial/Budget Permissions
//        // ============================================================
//        'PROJECT_BUDGET_VIEW' => 'project-management.project-management*budget.view',
//        'PROJECT_BUDGET_UPDATE' => 'project-management.project-management*budget.update',
//        'PROJECT_EXPENSE_CREATE' => 'project-management.project-management*expense.create',
//        'PROJECT_EXPENSE_APPROVE' => 'project-management.project-management*expense.approve',

        // ============================================================
//        // Reports Permissions
//        // ============================================================
//        'PROJECT_REPORT_VIEW' => 'project-management.project-management*report.view',
//        'PROJECT_REPORT_EXPORT' => 'project-management.project-management*report.export',
//        'PROJECT_REPORT_GENERATE' => 'project-management.project-management*report.generate',
    ]
];
