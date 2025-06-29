<?php

return [
    'permissions' => [
        // User module permissions
        'USER_VIEW' => 'users.user.view',
        'USER_LIST' => 'users.user.list',
        'USER_CREATE' => 'users.user.create',
        'USER_EDIT' => 'users.user.edit',
        'USER_DELETE' => 'users.user.delete',
        'USER_EXPORT' => 'users.user.export',

        'CLIENT_VIEW' => 'users.client.view',
        'CLIENT_LIST' => 'users.client.list',
        'CLIENT_CREATE' => 'users.client.create',
        'CLIENT_EDIT' => 'users.client.edit',
        'CLIENT_DELETE' => 'users.client.delete',
        'CLIENT_EXPORT' => 'users.client.export',

        'BROKER_VIEW' => 'users.broker.view',
        'BROKER_LIST' => 'users.broker.list',
        'BROKER_CREATE' => 'users.broker.create',
        'BROKER_EDIT' => 'users.broker.edit',
        'BROKER_DELETE' => 'users.broker.delete',
        'BROKER_EXPORT' => 'users.broker.export',

        'EMPLOYEE_VIEW' => 'users.employee.view',
        'EMPLOYEE_LIST' => 'users.employee.list',
        'EMPLOYEE_CREATE' => 'users.employee.create',
        'EMPLOYEE_EDIT' => 'users.employee.edit',
        'EMPLOYEE_DELETE' => 'users.employee.delete',
        'EMPLOYEE_EXPORT' => 'users.employee.export',

        'PROFILE_PERSONAL_INFO_UPDATE' => 'user-profile.personal-information.update',
        'PROFILE_CONTACT_INFO_UPDATE' => 'user-profile.contact-information.update',
        'PROFILE_PASSPORT_INFO_UPDATE' => 'user-profile.passport-information.update',
        'PROFILE_BANK_INFO_CREATE' => 'user-profile.bank-information.create',
        'PROFILE_BANK_INFO_UPDATE' => 'user-profile.bank-information.update',
        'PROFILE_BANK_INFO_DELETE' => 'user-profile.bank-information.delete',
        'PROFILE_ADDRESS_INFO_UPDATE' => 'user-profile.address-information.update',
        'PROFILE_MARITAL_STATUS_CREATE' => 'user-profile.marital-status.create',
        'PROFILE_MARITAL_STATUS_UPDATE' => 'user-profile.marital-status.update',
        'PROFILE_MARITAL_STATUS_DELETE' => 'user-profile.marital-status.delete',
        'PROFILE_SOCIAL_MEDIA_UPDATE' => 'user-profile.social-media-account.update',
        'PROFILE_BORDER_NUMBER_UPDATE' => 'user-profile.border-number-information.update',
        'PROFILE_RESIDENCE_INFO_UPDATE' => 'user-profile.residence-information.update',
        'PROFILE_ABOUT_ME_UPDATE' => 'user-profile.about-me-information.update',
        'PROFILE_CV_UPDATE' => 'user-profile.cv.update',
        'PROFILE_JOB_OFFER_UPDATE' => 'user-profile.job-offer.update',
        'PROFILE_CONTRACT_WORK_UPDATE' => 'user-profile.contract-work.update',
        'PROFILE_EMPLOYMENT_INFO_UPDATE' => 'user-profile.employment-information.update',

        // Role and permission module permissions
        'ROLE_VIEW' => 'roles.role.view',
        'ROLE_LIST' => 'roles.role.list',
        'ROLE_CREATE' => 'roles.role.create',
        'ROLE_EDIT' => 'roles.role.edit',
        'ROLE_DELETE' => 'roles.role.delete',
        'ROLE_EXPORT' => 'roles.role.export',

        // Company module permissions
        'COMPANY_VIEW' => 'company.company.view',
        'COMPANY_LIST' => 'company.company.list',
        'COMPANY_CREATE' => 'company.company.create',
        'COMPANY_EDIT' => 'company.company.edit',
        'COMPANY_DELETE' => 'company.company.delete',
        'COMPANY_EXPORT' => 'company.company.export',

        'BRANCH_VIEW' => 'company.branch.view',
        'BRANCH_LIST' => 'company.branch.list',
        'BRANCH_CREATE' => 'company.branch.create',
        'BRANCH_EDIT' => 'company.branch.edit',
        'BRANCH_DELETE' => 'company.branch.delete',
        'BRANCH_EXPORT' => 'company.branch.export',

        'MANAGEMENT_VIEW' => 'company.management.view',
        'MANAGEMENT_LIST' => 'company.management.list',
        'MANAGEMENT_CREATE' => 'company.management.create',
        'MANAGEMENT_EDIT' => 'company.management.edit',
        'MANAGEMENT_DELETE' => 'company.management.delete',
        'MANAGEMENT_EXPORT' => 'company.management.export',

        // Country module permissions
        'COUNTRY_VIEW' => 'country.country.view',
        'COUNTRY_LIST' => 'country.country.list',
        'COUNTRY_CREATE' => 'country.country.create',
        'COUNTRY_EDIT' => 'country.country.edit',
        'COUNTRY_DELETE' => 'country.country.delete',
        'COUNTRY_EXPORT' => 'country.country.export',

        'STATE_VIEW' => 'country.state.view',
        'STATE_LIST' => 'country.state.list',
        'STATE_CREATE' => 'country.state.create',
        'STATE_EDIT' => 'country.state.edit',
        'STATE_DELETE' => 'country.state.delete',
        'STATE_EXPORT' => 'country.state.export',

        'CITY_VIEW' => 'country.city.view',
        'CITY_LIST' => 'country.city.list',
        'CITY_CREATE' => 'country.city.create',
        'CITY_EDIT' => 'country.city.edit',
        'CITY_DELETE' => 'country.city.delete',
        'CITY_EXPORT' => 'country.city.export',

        // Nationality module permissions
        'NATIONALITY_VIEW' => 'nationality.nationality.view',
        'NATIONALITY_LIST' => 'nationality.nationality.list',
        'NATIONALITY_CREATE' => 'nationality.nationality.create',
        'NATIONALITY_EDIT' => 'nationality.nationality.edit',
        'NATIONALITY_DELETE' => 'nationality.nationality.delete',
        'NATIONALITY_EXPORT' => 'nationality.nationality.export',

        // Currency module permissions
        'CURRENCY_VIEW' => 'currency.currency.view',
        'CURRENCY_LIST' => 'currency.currency.list',
        'CURRENCY_CREATE' => 'currency.currency.create',
        'CURRENCY_EDIT' => 'currency.currency.edit',
        'CURRENCY_DELETE' => 'currency.currency.delete',
        'CURRENCY_EXPORT' => 'currency.currency.export',

        // General module permissions
        'ATTACHMENT_VIEW' => 'general.attachment.view',
        'ATTACHMENT_LIST' => 'general.attachment.list',
        'ATTACHMENT_CREATE' => 'general.attachment.create',
        'ATTACHMENT_EDIT' => 'general.attachment.edit',
        'ATTACHMENT_DELETE' => 'general.attachment.delete',
        'ATTACHMENT_EXPORT' => 'general.attachment.export',

        'NOTE_VIEW' => 'general.note.view',
        'NOTE_LIST' => 'general.note.list',
        'NOTE_CREATE' => 'general.note.create',
        'NOTE_EDIT' => 'general.note.edit',
        'NOTE_DELETE' => 'general.note.delete',
        'NOTE_EXPORT' => 'general.note.export',

        'COMMENT_VIEW' => 'general.comment.view',
        'COMMENT_LIST' => 'general.comment.list',
        'COMMENT_CREATE' => 'general.comment.create',
        'COMMENT_EDIT' => 'general.comment.edit',
        'COMMENT_DELETE' => 'general.comment.delete',
        'COMMENT_EXPORT' => 'general.comment.export',
    ]
];
