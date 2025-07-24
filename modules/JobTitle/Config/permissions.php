<?php

return [
    'permissions' => [
        // ================================================================================================
        // JOB TITLE & JOB TYPE PERMISSIONS
        // ================================================================================================

        // Job Title Management
        'ORGANIZATION_JOB_TITLE_VIEW' => 'organization.organization-list*job-title.view',
        'ORGANIZATION_JOB_TITLE_CREATE' => 'organization.organization-list*job-title.create',
        'ORGANIZATION_JOB_TITLE_UPDATE' => 'organization.organization-list*job-title.update',
        'ORGANIZATION_JOB_TITLE_DELETE' => 'organization.organization-list*job-title.delete',
        'ORGANIZATION_JOB_TITLE_LIST' => 'organization.organization-list*job-title.list',
        'ORGANIZATION_JOB_TITLE_ACTIVATE' => 'organization.organization-list*job-title.activate',
        'ORGANIZATION_JOB_TITLE_EXPORT' => 'organization.organization-list*job-title.export',

        // Job Type Management
        'ORGANIZATION_JOB_TYPE_VIEW' => 'organization.organization-list*job-type.view',
        'ORGANIZATION_JOB_TYPE_CREATE' => 'organization.organization-list*job-type.create',
        'ORGANIZATION_JOB_TYPE_UPDATE' => 'organization.organization-list*job-type.update',
        'ORGANIZATION_JOB_TYPE_DELETE' => 'organization.organization-list*job-type.delete',
        'ORGANIZATION_JOB_TYPE_LIST' => 'organization.organization-list*job-type.list',
        'ORGANIZATION_JOB_TYPE_ACTIVATE' => 'organization.organization-list*job-type.activate',
        'ORGANIZATION_JOB_TYPE_EXPORT' => 'organization.organization-list*job-type.export',
    ]
];
