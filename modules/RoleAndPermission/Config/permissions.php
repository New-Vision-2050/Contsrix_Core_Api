<?php

return [
    'permissions' => [
        // ================================================================================================
        // ROLE AND PERMISSION MODULE PERMISSIONS
        // ================================================================================================

        // Role Management
        'ROLE_LIST' => 'settings.roles.list',
        'ROLE_VIEW' => 'settings.roles.view',
        'ROLE_CREATE' => 'settings.roles.create',
        'ROLE_UPDATE' => 'settings.roles.update',
        'ROLE_DELETE' => 'settings.roles.delete',
        'ROLE_ACTIVATE' => 'settings.roles.activate',

        // Permission Management
        'PERMISSION_LIST' => 'settings.permissions.list',
        'PERMISSION_CREATE' => 'settings.permissions.create',
        'PERMISSION_UPDATE' => 'settings.permissions.update',
        'PERMISSION_DELETE' => 'settings.permissions.delete',
        'PERMISSION_ACTIVATE' => 'settings.permissions.activate',
        'PERMISSION_VIEW' => 'settings.permissions.view',
        'PERMISSION_ASSIGN' => 'settings.permissions.update',
    ]
];
