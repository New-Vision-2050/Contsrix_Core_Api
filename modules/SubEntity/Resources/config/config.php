<?php

use Modules\User\Models\User;

return [
    'available_super_entities' => [
        [
            'id' => 'users',
            'name' => [
                'ar' => 'المستخدمين',
                'en' => 'Users'
            ],
            'model' => User::class,
            'registration_forms' => [
                [
                    "name" => [
                        'ar' => 'الموظفين',
                        'en' => 'Employees'
                    ],
                    'slug' => 'employee'
                ],
                [
                    "name" => [
                        'ar' => 'العملاء',
                        'en' => 'Customers'
                    ],
                    'slug' => 'customer'
                ],
                 [
                    "name" => [
                        'ar' => 'الوسطاء',
                        'en' => 'Resellers'
                    ],
                    'slug' => 'reseller'
                ]
            ]
        ],
    ]
];
