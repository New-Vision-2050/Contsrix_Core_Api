<?php

return [
    'permissions' => [
        // ================================================================================================
        // USER MANAGEMENT PERMISSIONS
        // ================================================================================================

        // User module permissions
        'USER_VIEW' => 'users.user-list*user-list.view',
        'USER_LIST' => 'users.user-list*user-list.list',
        'USER_CREATE' => 'users.user-list*user-list.create',
        'USER_UPDATE' => 'users.user-list*user-list.update',
        'USER_DELETE' => 'users.user-list*user-list.delete',
        'USER_EXPORT' => 'users.user-list*user-list.export',

//        'CLIENT_VIEW' => 'users.client.view',
//        'CLIENT_LIST' => 'users.client.list',
//        'CLIENT_CREATE' => 'users.client.create',
//        'CLIENT_UPDATE' => 'users.client.update',
//        'CLIENT_DELETE' => 'users.client.delete',
//        'CLIENT_EXPORT' => 'users.client.export',
//
//        'BROKER_VIEW' => 'users.broker.view',
//        'BROKER_LIST' => 'users.broker.list',
//        'BROKER_CREATE' => 'users.broker.create',
//        'BROKER_UPDATE' => 'users.broker.update',
//        'BROKER_DELETE' => 'users.broker.delete',
//        'BROKER_EXPORT' => 'users.broker.export',
//
//        'EMPLOYEE_VIEW' => 'users.employee.view',
//        'EMPLOYEE_LIST' => 'users.employee.list',
//        'EMPLOYEE_CREATE' => 'users.employee.create',
//        'EMPLOYEE_UPDATE' => 'users.employee.update',
//        'EMPLOYEE_DELETE' => 'users.employee.delete',
//        'EMPLOYEE_EXPORT' => 'users.employee.export',
    ]
];
