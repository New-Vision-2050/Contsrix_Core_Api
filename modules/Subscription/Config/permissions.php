<?php

return [
    'permissions' => [
        // ================================================================================================
        // SUBSCRIPTION MODULE PERMISSIONS
        // ================================================================================================
        
        // Package Management
        'PACKAGE_LIST' => 'subscription.package.list',
        'PACKAGE_VIEW' => 'subscription.package.view',
        'PACKAGE_CREATE' => 'subscription.package.create',
        'PACKAGE_UPDATE' => 'subscription.package.update',
        'PACKAGE_DELETE' => 'subscription.package.delete',
        'PACKAGE_EXPORT' => 'subscription.package.export',
        'PACKAGE_ACTIVATE' => 'subscription.package.activate',

        // Company Access Program Management
        'COMPANY_ACCESS_PROGRAM_LIST' => 'subscription.company-access-program.list',
        'COMPANY_ACCESS_PROGRAM_VIEW' => 'subscription.company-access-program.view',
        'COMPANY_ACCESS_PROGRAM_CREATE' => 'subscription.company-access-program.create',
        'COMPANY_ACCESS_PROGRAM_UPDATE' => 'subscription.company-access-program.update',
        'COMPANY_ACCESS_PROGRAM_DELETE' => 'subscription.company-access-program.delete',
        'COMPANY_ACCESS_PROGRAM_EXPORT' => 'subscription.company-access-program.export',
        'COMPANY_ACCESS_PROGRAM_ACTIVATE' => 'subscription.company-access-program.activate',
    ]
];
