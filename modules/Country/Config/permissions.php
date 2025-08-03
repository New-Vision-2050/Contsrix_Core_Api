<?php

return [
    'permissions' => [
        // ================================================================================================
        // GEOGRAPHIC DATA PERMISSIONS
        // ================================================================================================

        // Country Management
        'COUNTRY_VIEW' => 'settings.program-settings*country.view',
        'COUNTRY_LIST' => 'settings.program-settings*country.list',
        'COUNTRY_CREATE' => 'settings.program-settings*country.create',
        'COUNTRY_UPDATE' => 'settings.program-settings*country.update',
        'COUNTRY_DELETE' => 'settings.program-settings*country.delete',
        'COUNTRY_EXPORT' => 'settings.program-settings*country.export',

        // State Management
        'STATE_VIEW' => 'settings.program-settings*state.view',
        'STATE_LIST' => 'settings.program-settings*state.list',
        'STATE_CREATE' => 'settings.program-settings*state.create',
        'STATE_UPDATE' => 'settings.program-settings*state.update',
        'STATE_DELETE' => 'settings.program-settings*state.delete',
        'STATE_EXPORT' => 'settings.program-settings*state.export',

        // City Management
        'CITY_VIEW' => 'settings.program-settings*city.view',
        'CITY_LIST' => 'settings.program-settings*city.list',
        'CITY_CREATE' => 'settings.program-settings*city.create',
        'CITY_UPDATE' => 'settings.program-settings*city.update',
        'CITY_DELETE' => 'settings.program-settings*city.delete',
        'CITY_EXPORT' => 'settings.program-settings*city.export',
    ]
];
