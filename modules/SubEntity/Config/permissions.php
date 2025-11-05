<?php

return [
    'permissions' => [
        // ================================================================================================
        // SUB ENTITY MODULE PERMISSIONS
        // ================================================================================================

        // Sub Entity Management
        'SUB_ENTITY_LIST' => 'program-management.users*sub-entity.list',
//        'SUB_ENTITY_VIEW' => 'program-management.users*sub-entity.view',
        'SUB_ENTITY_CREATE' => 'program-management.users*sub-entity.create',
        'SUB_ENTITY_UPDATE' => 'program-management.users*sub-entity.update',
        'SUB_ENTITY_DELETE' => 'program-management.users*sub-entity.delete',
        'SUB_ENTITY_ACTIVATE' => 'program-management.users*sub-entity.activate',
        'SUB_ENTITY_EXPORT' => 'program-management.users*sub-entity.export',

        // Sub Entity Records Management
        'SUB_ENTITY_RECORDS_LIST' => 'program-management.users*sub-entity-records.list',
        'SUB_ENTITY_RECORDS_EXPORT' => 'program-management.users*sub-entity-records.export',



        // Super Entity Management
        'SUPER_ENTITY_LIST' => 'program-management.users*super-entity.list',
        'SUPER_ENTITY_VIEW' => 'program-management.users*super-entity.view',

    ]
];
