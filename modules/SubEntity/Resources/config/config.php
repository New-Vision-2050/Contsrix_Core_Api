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
        ],
    ]
];
