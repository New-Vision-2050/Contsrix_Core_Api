<?php

return [
    'permissions' => [
        // ================================================================================================
        // SYSTEM SETTINGS & CONFIGURATION PERMISSIONS
        // ================================================================================================

        // System Configuration
        'IDENTIFIER_LIST' => 'settings.program-settings*identifier.list',

        // Login Way Management
        'LOGIN_WAY_CREATE' => 'settings.program-settings*login-way.create',
        'LOGIN_WAY_LIST' => 'settings.program-settings*login-way.list',
        'LOGIN_WAY_UPDATE' => 'settings.program-settings*login-way.update',
        'LOGIN_WAY_VIEW' => 'settings.program-settings*login-way.view',
        'LOGIN_WAY_DELETE' => 'settings.program-settings*login-way.delete',
        'LOGIN_WAY_ACTIVATE' => 'settings.program-settings*login-way.activate',

        // Company Profile Settings
//        'COMPANY_PROFILE_VIEW' => 'settings.program-settings*company-profile.view',

        // Driver Management
        'DRIVER_VIEW' => 'settings.program-settings*driver.view',
        'DRIVER_UPDATE' => 'settings.program-settings*driver.update',
    ]
];
