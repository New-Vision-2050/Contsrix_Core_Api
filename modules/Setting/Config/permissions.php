<?php

return [
    'permissions' => [
        // ================================================================================================
        // SYSTEM SETTINGS & CONFIGURATION PERMISSIONS
        // ================================================================================================

        // System Configuration
        'IDENTIFIER_LIST' => 'settings.identifier.list',

        // Login Way Management
        'LOGIN_WAY_CREATE' => 'settings.login-way.create',
        'LOGIN_WAY_LIST' => 'settings.login-way.list',
        'LOGIN_WAY_UPDATE' => 'settings.login-way.update',
        'LOGIN_WAY_VIEW' => 'settings.login-way.view',
        'LOGIN_WAY_DELETE' => 'settings.login-way.delete',
        'LOGIN_WAY_ACTIVATE' => 'settings.login-way.activate',

        // Company Profile Settings
//        'COMPANY_PROFILE_VIEW' => 'settings.company-profile.view',

        // Driver Management
        'DRIVER_VIEW' => 'settings.driver.view',
        'DRIVER_UPDATE' => 'settings.driver.update',
    ]
];
