<?php

return [
    'permissions' => [
        // ================================================================================================
        // COMPANY MANAGEMENT PERMISSIONS
        // ================================================================================================

        // Company module permissions
        'COMPANY_VIEW' => 'companies.companies-list*companies-list.view',
        'COMPANY_LIST' => 'companies.companies-list*companies-list.list',
        'COMPANY_CREATE' => 'companies.companies-list*companies-list.create',
        'COMPANY_UPDATE' => 'companies.companies-list*companies-list.update',
        'COMPANY_DELETE' => 'companies.companies-list*companies-list.delete',
        'COMPANY_EXPORT' => 'companies.companies-list*companies-list.export',

        // ================================================================================================
        // COMPANY PROFILE PERMISSIONS
        // ================================================================================================

        // Official Data Management
        'COMPANY_PROFILE_OFFICIAL_DATA_UPDATE' => 'settings.company-profile*official-data.update',
        'COMPANY_PROFILE_OFFICIAL_DATA_VIEW' => 'settings.company-profile*official-data.view',

        'COMPANY_PROFILE_SUPPORT_DATA_VIEW' => 'settings.company-profile*support-data.view',

        // Legal Data Management
        'COMPANY_PROFILE_LEGAL_DATA_VIEW' => 'settings.company-profile*legal-data.view',
        'COMPANY_PROFILE_LEGAL_DATA_UPDATE' => 'settings.company-profile*legal-data.update',
        'COMPANY_PROFILE_LEGAL_DATA_CREATE' => 'settings.company-profile*legal-data.create',
        'COMPANY_PROFILE_LEGAL_DATA_DELETE' => 'settings.company-profile*legal-data.delete',

        // Address Management
        'COMPANY_PROFILE_ADDRESS_VIEW' => 'settings.company-profile*address.view',
        'COMPANY_PROFILE_ADDRESS_UPDATE' => 'settings.company-profile*address.update',

        // Branch Management
        'COMPANY_PROFILE_BRANCH_LIST' => 'settings.company-profile*branch.list',
        'COMPANY_PROFILE_BRANCH_VIEW' => 'settings.company-profile*branch.view',
        'COMPANY_PROFILE_BRANCH_UPDATE' => 'settings.company-profile*branch.update',
        'COMPANY_PROFILE_BRANCH_CREATE' => 'settings.company-profile*branch.create',

        // Official Document Management
        'COMPANY_PROFILE_OFFICIAL_DOCUMENT_CREATE' => 'settings.company-profile*official-document.create',
        'COMPANY_PROFILE_OFFICIAL_DOCUMENT_VIEW' => 'settings.company-profile*official-document.view',
        'COMPANY_PROFILE_OFFICIAL_DOCUMENT_UPDATE' => 'settings.company-profile*official-document.update',
        'COMPANY_PROFILE_OFFICIAL_DOCUMENT_DELETE' => 'settings.company-profile*official-document.delete',

        // ================================================================================================
        // ORGANIZATION STRUCTURE PERMISSIONS
        // ================================================================================================

        // Branch Management
        'ORGANIZATION_BRANCH_VIEW' => 'human-resources.organization-list*branch.view',
        'ORGANIZATION_BRANCH_CREATE' => 'human-resources.organization-list*branch.create',
        'ORGANIZATION_BRANCH_UPDATE' => 'human-resources.organization-list*branch.update',
        'ORGANIZATION_BRANCH_DELETE' => 'human-resources.organization-list*branch.delete',

        // Management Hierarchy
        'ORGANIZATION_MANAGEMENT_VIEW' => 'human-resources.organization-list*management.view',
        'ORGANIZATION_MANAGEMENT_EXPORT' => 'human-resources.organization-list*management.export',
        'ORGANIZATION_MANAGEMENT_CREATE' => 'human-resources.organization-list*management.create',
        'ORGANIZATION_MANAGEMENT_UPDATE' => 'human-resources.organization-list*management.update',
        'ORGANIZATION_MANAGEMENT_DELETE' => 'human-resources.organization-list*management.delete',

        // Department Management
        'ORGANIZATION_DEPARTMENT_VIEW' => 'human-resources.organization-list*department.view',
        'ORGANIZATION_DEPARTMENT_EXPORT' => 'human-resources.organization-list*department.export',
        'ORGANIZATION_DEPARTMENT_CREATE' => 'human-resources.organization-list*department.create',
        'ORGANIZATION_DEPARTMENT_UPDATE' => 'human-resources.organization-list*department.update',
        'ORGANIZATION_DEPARTMENT_DELETE' => 'human-resources.organization-list*department.delete',

        // Organization Users
        'ORGANIZATION_USERS_VIEW' => 'human-resources.organization-list*users.view',

        'HUMAN_RESOURCES_CHARTS_VIEW' => 'human-resources.work-panel*charts.view',


        'HUMAN_RESOURCES_PROCEDURES_VIEW' => 'human-resources.work-panel*procedures.view',

        'HUMAN_RESOURCES_SERVICES_VIEW' => 'human-resources.work-panel*services.view',

    ]
];
