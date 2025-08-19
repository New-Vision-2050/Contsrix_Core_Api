<?php

return [
    'permissions' => [
        // ================================================================================================
        // LEAVE MODULE PERMISSIONS
        // Human Resource Settings - Leave Management
        // ================================================================================================

        // Leave Type Management
        'LEAVE_TYPE_LIST' => 'human-resources.settings*leave-type.list',
        'LEAVE_TYPE_VIEW' => 'human-resources.settings*leave-type.view',
        'LEAVE_TYPE_CREATE' => 'human-resources.settings*leave-type.create',
        'LEAVE_TYPE_UPDATE' => 'human-resources.settings*leave-type.update',
        'LEAVE_TYPE_DELETE' => 'human-resources.settings*leave-type.delete',
        'LEAVE_TYPE_ACTIVATE' => 'human-resources.settings*leave-type.activate',
        'LEAVE_TYPE_EXPORT' => 'human-resources.settings*leave-type.export',

        // Leave Policy Management
        'LEAVE_POLICY_LIST' => 'human-resources.settings*leave-policy.list',
        'LEAVE_POLICY_VIEW' => 'human-resources.settings*leave-policy.view',
        'LEAVE_POLICY_CREATE' => 'human-resources.settings*leave-policy.create',
        'LEAVE_POLICY_UPDATE' => 'human-resources.settings*leave-policy.update',
        'LEAVE_POLICY_DELETE' => 'human-resources.settings*leave-policy.delete',
        'LEAVE_POLICY_ACTIVATE' => 'human-resources.settings*leave-policy.activate',
        'LEAVE_POLICY_EXPORT' => 'human-resources.settings*leave-policy.export',

        // Public Holiday Management
        'PUBLIC_HOLIDAY_LIST' => 'human-resources.settings*public-holiday.list',
        'PUBLIC_HOLIDAY_VIEW' => 'human-resources.settings*public-holiday.view',
        'PUBLIC_HOLIDAY_CREATE' => 'human-resources.settings*public-holiday.create',
        'PUBLIC_HOLIDAY_UPDATE' => 'human-resources.settings*public-holiday.update',
        'PUBLIC_HOLIDAY_DELETE' => 'human-resources.settings*public-holiday.delete',
        'PUBLIC_HOLIDAY_ACTIVATE' => 'human-resources.settings*public-holiday.activate',
        'PUBLIC_HOLIDAY_EXPORT' => 'human-resources.settings*public-holiday.export',
    ]
];
