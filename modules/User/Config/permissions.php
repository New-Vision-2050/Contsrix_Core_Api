<?php

return [
    'permissions' => [
        // ================================================================================================
        // USER MANAGEMENT PERMISSIONS
        // ================================================================================================

        // User module permissions
        'USER_VIEW' => 'users.user-list*users.view',
        'USER_LIST' => 'users.user-list*users.list',
        'USER_CREATE' => 'users.user-list*users.create',
        'USER_UPDATE' => 'users.user-list*users.update',
        'USER_DELETE' => 'users.user-list*users.delete',
        'USER_EXPORT' => 'users.user-list*users.export',

        'CLIENT_VIEW' => 'clients.clients*clients.view',
        'CLIENT_LIST' => 'clients.clients*clients.list',
        'CLIENT_CREATE' => 'clients.clients*clients.create',
        'CLIENT_UPDATE' => 'clients.clients*clients.update',
        'CLIENT_DELETE' => 'clients.clients*clients.delete',
        'CLIENT_EXPORT' => 'clients.clients*clients.export',
//
        'BROKER_VIEW' => 'clients.brokers*brokers.view',
        'BROKER_LIST' => 'clients.brokers*brokers.list',
        'BROKER_CREATE' => 'clients.brokers*brokers.create',
        'BROKER_UPDATE' => 'clients.brokers*brokers.update',
        'BROKER_DELETE' => 'clients.brokers*brokers.delete',
        'BROKER_EXPORT' => 'clients.brokers*brokers.export',
//
//        'EMPLOYEE_VIEW' => 'users.employee.view',
//        'EMPLOYEE_LIST' => 'users.employee.list',
//        'EMPLOYEE_CREATE' => 'users.employee.create',
//        'EMPLOYEE_UPDATE' => 'users.employee.update',
//        'EMPLOYEE_DELETE' => 'users.employee.delete',
//        'EMPLOYEE_EXPORT' => 'users.employee.export',
    ]
];
