<?php

return [
    'permissions' => [
        // ================================================================================================
        // ROLE AND PERMISSION MODULE PERMISSIONS
        // ================================================================================================

        // Role Management
        'ROLE_LIST' => 'settings.roles*roles.list',
        'ROLE_VIEW' => 'settings.roles*roles.view',
        'ROLE_CREATE' => 'settings.roles*roles.create',
        'ROLE_UPDATE' => 'settings.roles*roles.update',
        'ROLE_DELETE' => 'settings.roles*roles.delete',
        'ROLE_ACTIVATE' => 'settings.roles*roles.activate',

        // Permission Management
        'PERMISSION_LIST' => 'settings.permissions*permissions.list',
        'PERMISSION_CREATE' => 'settings.permissions*permissions.create',
        'PERMISSION_UPDATE' => 'settings.permissions*permissions.update',
        'PERMISSION_DELETE' => 'settings.permissions*permissions.delete',
        'PERMISSION_ACTIVATE' => 'settings.permissions*permissions.activate',
        'PERMISSION_VIEW' => 'settings.permissions*permissions.view',
    ]
];
