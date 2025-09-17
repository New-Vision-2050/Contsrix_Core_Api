<?php

return [
    'permissions' => [
        // ================================================================================================
        // SUBSCRIPTION MODULE PERMISSIONS
        // ================================================================================================

        // Package Management
        'PACKAGE_LIST' => 'permissions.program-and-packages*package.list',
        'PACKAGE_VIEW' => 'permissions.program-and-packages*package.view',
        'PACKAGE_CREATE' => 'permissions.program-and-packages*package.create',
        'PACKAGE_UPDATE' => 'permissions.program-and-packages*package.update',
        'PACKAGE_DELETE' => 'permissions.program-and-packages*package.delete',
        'PACKAGE_EXPORT' => 'permissions.program-and-packages*package.export',
        'PACKAGE_ACTIVATE' => 'permissions.program-and-packages*package.activate',

        // Company Access Program Management
        'COMPANY_ACCESS_PROGRAM_LIST' => 'permissions.program-and-packages*company-access-program.list',
        'COMPANY_ACCESS_PROGRAM_VIEW' => 'permissions.program-and-packages*company-access-program.view',
        'COMPANY_ACCESS_PROGRAM_CREATE' => 'permissions.program-and-packages*company-access-program.create',
        'COMPANY_ACCESS_PROGRAM_UPDATE' => 'permissions.program-and-packages*company-access-program.update',
        'COMPANY_ACCESS_PROGRAM_DELETE' => 'permissions.program-and-packages*company-access-program.delete',
        'COMPANY_ACCESS_PROGRAM_EXPORT' => 'permissions.program-and-packages*company-access-program.export',
        'COMPANY_ACCESS_PROGRAM_ACTIVATE' => 'permissions.program-and-packages*company-access-program.activate',
    ]
];
