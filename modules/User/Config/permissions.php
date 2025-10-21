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

//        'CLIENT_VIEW' => 'client-relations.clients*clients.view',
//        'CLIENT_LIST' => 'client-relations.clients*clients.list',
//        'CLIENT_CREATE' => 'client-relations.clients*clients.create',
//        'CLIENT_UPDATE' => 'client-relations.clients*clients.update',
//        'CLIENT_DELETE' => 'client-relations.clients*clients.delete',
        'CLIENT_SETTING_UPDATE' => 'client-relations.client-setting*client-setting.update',
//
//        'BROKER_VIEW' => 'client-relations.brokers*brokers.view',
//        'BROKER_LIST' => 'client-relations.brokers*brokers.list',
//        'BROKER_CREATE' => 'client-relations.brokers*brokers.create',
//        'BROKER_UPDATE' => 'client-relations.brokers*brokers.update',
//        'BROKER_DELETE' => 'client-relations.brokers*brokers.delete',
//        'BROKER_EXPORT' => 'client-relations.brokers*brokers.export',
//
//        'EMPLOYEE_VIEW' => 'users.employee.view',
//        'EMPLOYEE_LIST' => 'users.employee.list',
//        'EMPLOYEE_CREATE' => 'users.employee.create',
//        'EMPLOYEE_UPDATE' => 'users.employee.update',
//        'EMPLOYEE_DELETE' => 'users.employee.delete',
//        'EMPLOYEE_EXPORT' => 'users.employee.export',
    ]
];
