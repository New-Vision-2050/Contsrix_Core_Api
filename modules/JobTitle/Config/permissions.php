<?php

return [
    'permissions' => [
        // ================================================================================================
        // JOB TITLE & JOB TYPE PERMISSIONS
        // ================================================================================================

        // Job Title Management
        'ORGANIZATION_JOB_TITLE_VIEW' => 'human-resources.organization-list*job-title.view',
        'ORGANIZATION_JOB_TITLE_CREATE' => 'human-resources.organization-list*job-title.create',
        'ORGANIZATION_JOB_TITLE_UPDATE' => 'human-resources.organization-list*job-title.update',
        'ORGANIZATION_JOB_TITLE_DELETE' => 'human-resources.organization-list*job-title.delete',
        'ORGANIZATION_JOB_TITLE_LIST' => 'human-resources.organization-list*job-title.list',
        'ORGANIZATION_JOB_TITLE_ACTIVATE' => 'human-resources.organization-list*job-title.activate',
        'ORGANIZATION_JOB_TITLE_EXPORT' => 'human-resources.organization-list*job-title.export',

        // Job Type Management
        'ORGANIZATION_JOB_TYPE_VIEW' => 'human-resources.organization-list*job-type.view',
        'ORGANIZATION_JOB_TYPE_CREATE' => 'human-resources.organization-list*job-type.create',
        'ORGANIZATION_JOB_TYPE_UPDATE' => 'human-resources.organization-list*job-type.update',
        'ORGANIZATION_JOB_TYPE_DELETE' => 'human-resources.organization-list*job-type.delete',
        'ORGANIZATION_JOB_TYPE_LIST' => 'human-resources.organization-list*job-type.list',
        'ORGANIZATION_JOB_TYPE_ACTIVATE' => 'human-resources.organization-list*job-type.activate',
        'ORGANIZATION_JOB_TYPE_EXPORT' => 'human-resources.organization-list*job-type.export',
    ]
];
