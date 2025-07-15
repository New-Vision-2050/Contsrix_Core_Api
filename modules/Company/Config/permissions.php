<?php

return [
    'permissions' => [
        // ================================================================================================
        // COMPANY MANAGEMENT PERMISSIONS
        // ================================================================================================

        // Company module permissions
        'COMPANY_VIEW' => 'companies.company.view',
        'COMPANY_LIST' => 'companies.company.list',
        'COMPANY_CREATE' => 'companies.company.create',
        'COMPANY_UPDATE' => 'companies.company.update',
        'COMPANY_DELETE' => 'companies.company.delete',
        'COMPANY_EXPORT' => 'companies.company.export',

        // ================================================================================================
        // COMPANY PROFILE PERMISSIONS
        // ================================================================================================

        // Official Data Management
        'COMPANY_PROFILE_OFFICIAL_DATA_UPDATE' => 'company-profile.official-data.update',
        'COMPANY_PROFILE_OFFICIAL_DATA_VIEW' => 'company-profile.official-data.view',

        // Legal Data Management
        'COMPANY_PROFILE_LEGAL_DATA_VIEW' => 'company-profile.legal-data.view',
        'COMPANY_PROFILE_LEGAL_DATA_UPDATE' => 'company-profile.legal-data.update',
        'COMPANY_PROFILE_LEGAL_DATA_CREATE' => 'company-profile.legal-data.create',
        'COMPANY_PROFILE_LEGAL_DATA_DELETE' => 'company-profile.legal-data.delete',

        // Address Management
        'COMPANY_PROFILE_ADDRESS_VIEW' => 'company-profile.address.view',
        'COMPANY_PROFILE_ADDRESS_UPDATE' => 'company-profile.address.update',

        // Branch Management
        'COMPANY_PROFILE_BRANCH_LIST' => 'company-profile.branch.list',
        'COMPANY_PROFILE_BRANCH_VIEW' => 'company-profile.branch.view',
        'COMPANY_PROFILE_BRANCH_UPDATE' => 'company-profile.branch.update',
        'COMPANY_PROFILE_BRANCH_CREATE' => 'company-profile.branch.create',

        // Official Document Management
        'COMPANY_PROFILE_OFFICIAL_DOCUMENT_CREATE' => 'company-profile.official-document.create',
        'COMPANY_PROFILE_OFFICIAL_DOCUMENT_VIEW' => 'company-profile.official-document.view',
        'COMPANY_PROFILE_OFFICIAL_DOCUMENT_UPDATE' => 'company-profile.official-document.update',
        'COMPANY_PROFILE_OFFICIAL_DOCUMENT_DELETE' => 'company-profile.official-document.delete',

        // ================================================================================================
        // ORGANIZATION STRUCTURE PERMISSIONS
        // ================================================================================================

        // Branch Management
        'ORGANIZATION_BRANCH_VIEW' => 'organization.branch.view',
        'ORGANIZATION_BRANCH_CREATE' => 'organization.branch.create',
        'ORGANIZATION_BRANCH_UPDATE' => 'organization.branch.update',
        'ORGANIZATION_BRANCH_DELETE' => 'organization.branch.delete',

        // Management Hierarchy
        'ORGANIZATION_MANAGEMENT_VIEW' => 'organization.management.view',
        'ORGANIZATION_MANAGEMENT_EXPORT' => 'organization.management.export',
        'ORGANIZATION_MANAGEMENT_CREATE' => 'organization.management.create',
        'ORGANIZATION_MANAGEMENT_UPDATE' => 'organization.management.update',
        'ORGANIZATION_MANAGEMENT_DELETE' => 'organization.management.delete',

        // Department Management
        'ORGANIZATION_DEPARTMENT_VIEW' => 'organization.department.view',
        'ORGANIZATION_DEPARTMENT_EXPORT' => 'organization.department.export',
        'ORGANIZATION_DEPARTMENT_CREATE' => 'organization.department.create',
        'ORGANIZATION_DEPARTMENT_UPDATE' => 'organization.department.update',
        'ORGANIZATION_DEPARTMENT_DELETE' => 'organization.department.delete',

        // Organization Users
        'ORGANIZATION_USERS_VIEW' => 'organization.users.view',
    ]
];
