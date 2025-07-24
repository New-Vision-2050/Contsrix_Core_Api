<?php

return [
    'permissions' => [
        // ================================================================================================
        // USER PROFILE PERMISSIONS
        // ================================================================================================

        // User Profile module permissions
        'USER_PROFILE_DATA_VIEW' => 'settings.user-profile*data.view',
        'USER_PROFILE_DATA_UPDATE' => 'settings.user-profile*data.update',
        'USER_PROFILE_CONTACT_VIEW' => 'settings.user-profile*contact.view',
        'USER_PROFILE_CONTACT_UPDATE' => 'settings.user-profile*contact.update',
        'USER_PROFILE_IDENTITY_VIEW' => 'settings.user-profile*identity.view',
        'USER_PROFILE_IDENTITY_UPDATE' => 'settings.user-profile*identity.update',

        // Personal Information
        'PROFILE_PERSONAL_INFO_VIEW' => 'settings.user-profile*personal-information.view',
        'PROFILE_PERSONAL_INFO_UPDATE' => 'settings.user-profile*personal-information.update',

        // Contact Information
        'PROFILE_CONTACT_INFO_VIEW' => 'settings.user-profile*contact-information.view',
        'PROFILE_CONTACT_INFO_UPDATE' => 'settings.user-profile*contact-information.update',

        // Passport Information
        'PROFILE_PASSPORT_INFO_VIEW' => 'settings.user-profile*passport-information.view',
        'PROFILE_PASSPORT_INFO_UPDATE' => 'settings.user-profile*passport-information.update',

        // Bank Information
        'PROFILE_BANK_INFO_VIEW' => 'settings.user-profile*bank-information.view',
        'PROFILE_BANK_INFO_CREATE' => 'settings.user-profile*bank-information.create',
        'PROFILE_BANK_INFO_UPDATE' => 'settings.user-profile*bank-information.update',
        'PROFILE_BANK_INFO_DELETE' => 'settings.user-profile*bank-information.delete',

        // Family Information
        'PROFILE_FAMILY_INFO_VIEW' => 'settings.user-profile*family-information.view',
        'PROFILE_FAMILY_INFO_CREATE' => 'settings.user-profile*family-information.create',
        'PROFILE_FAMILY_INFO_UPDATE' => 'settings.user-profile*family-information.update',
        'PROFILE_FAMILY_INFO_DELETE' => 'settings.user-profile*family-information.delete',

        // Education Information
        'PROFILE_EDUCATION_VIEW' => 'settings.user-profile*education.view',
        'PROFILE_EDUCATION_CREATE' => 'settings.user-profile*education.create',
        'PROFILE_EDUCATION_UPDATE' => 'settings.user-profile*education.update',
        'PROFILE_EDUCATION_DELETE' => 'settings.user-profile*education.delete',

        // Experience Information
        'PROFILE_EXPERIENCE_VIEW' => 'settings.user-profile*experience.view',
        'PROFILE_EXPERIENCE_CREATE' => 'settings.user-profile*experience.create',
        'PROFILE_EXPERIENCE_UPDATE' => 'settings.user-profile*experience.update',
        'PROFILE_EXPERIENCE_DELETE' => 'settings.user-profile*experience.delete',

        // Courses Information
        'PROFILE_COURSES_VIEW' => 'settings.user-profile*courses.view',
        'PROFILE_COURSES_CREATE' => 'settings.user-profile*courses.create',
        'PROFILE_COURSES_UPDATE' => 'settings.user-profile*courses.update',
        'PROFILE_COURSES_DELETE' => 'settings.user-profile*courses.delete',

        // Work License Information
        'PROFILE_WORK_LICENSE_VIEW' => 'settings.user-profile*work-license.view',
        'PROFILE_WORK_LICENSE_CREATE' => 'settings.user-profile*work-license.create',
        'PROFILE_WORK_LICENSE_UPDATE' => 'settings.user-profile*work-license.update',
    ]
];
