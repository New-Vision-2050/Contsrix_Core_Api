<?php

return [
    'permissions' => [
        // ================================================================================================
        // GEOGRAPHIC DATA PERMISSIONS
        // ================================================================================================
        
        // Country Management
        'COUNTRY_VIEW' => 'country.country.view',
        'COUNTRY_LIST' => 'country.country.list',
        'COUNTRY_CREATE' => 'country.country.create',
        'COUNTRY_UPDATE' => 'country.country.update',
        'COUNTRY_DELETE' => 'country.country.delete',
        'COUNTRY_EXPORT' => 'country.country.export',

        // State Management
        'STATE_VIEW' => 'country.state.view',
        'STATE_LIST' => 'country.state.list',
        'STATE_CREATE' => 'country.state.create',
        'STATE_UPDATE' => 'country.state.update',
        'STATE_DELETE' => 'country.state.delete',
        'STATE_EXPORT' => 'country.state.export',

        // City Management
        'CITY_VIEW' => 'country.city.view',
        'CITY_LIST' => 'country.city.list',
        'CITY_CREATE' => 'country.city.create',
        'CITY_UPDATE' => 'country.city.update',
        'CITY_DELETE' => 'country.city.delete',
        'CITY_EXPORT' => 'country.city.export',
    ]
];
