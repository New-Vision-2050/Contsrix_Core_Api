<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

use Modules\SubscriptionSystem\Feature\Models\Feature;
use Modules\SubscriptionSystem\Modules\Models\Module;
use Spatie\Permission\Models\Permission;

class ModuleStructureSeeder extends Seeder
{
    public function run(): void
    {
        $createModule = function (array $name, string $slug, ?string $parentId = null) {
            return Module::create(
                [
                    'name'      => $name,
                    'slug'      => $slug,
                    'parent_id' => $parentId,
                ]
            );
        };

        $createFeature = function (array $name, string $slug, string $moduleId) {
            $feature = Feature::create(
                [
                    'name' => $name,
                    'slug' => $slug,
                    'module_id' => $moduleId
                ]
            );

            // Permission::firstOrCreate(attributes: ['name' => $slug]);
            return $feature;
        };

        /** Root Modules */
        $companies      = $createModule(['en' => 'Companies',        'ar' => 'الشركات'], 'companies');
        $users          = $createModule(['en' => 'Users',            'ar' => 'المستخدمين'], 'users');
        $programsRoot   = $createModule(['en' => 'Program Manager',  'ar' => 'ادارة البرامج'], 'program-management');
        $settingsRoot   = $createModule(['en' => 'Settings',         'ar' => 'الإعدادات'], 'settings');

        /** Features for companies & users */
        $createFeature(['en' => 'Add Company',  'ar' => 'اضافة شركة'], 'add-companies', $companies->id);
        $createFeature(['en' => 'Add User',     'ar' => 'اضافة مستخدم'], 'add-user', $users->id);

        /** Program Management Submodules */
        $createFeature(['en' => 'Sub Tables',     'ar' => 'الجداول الفرعيه'], 'program-management-sub-tables', $programsRoot->id);
        $createFeature(['en' => 'Table Structure','ar' => 'هيكل الجدول'], 'program-management-main-tables-table-structure', $programsRoot->id);
        $createFeature(['en' => 'Table Content',  'ar' => 'محتويات الجدول'], 'program-management-main-tables-table-content', $programsRoot->id);
        $createFeature(['en' => 'Table Settings', 'ar' => 'اعدادات الجدول'], 'program-management-main-tables-table-settings', $programsRoot->id);

        /** Settings Submodules */
        $userProfileSettings = $createModule(['en' => 'User Profile Settings', 'ar' => 'الملف الشخصي'], 'user-profile-settings', $settingsRoot->id);
        $companySettings     = $createModule(['en' => 'Company Settings', 'ar' => 'ملف الشركة'], 'company-settings', $settingsRoot->id);
        $programSettings     = $createModule(['en' => 'Program Settings', 'ar' => 'إعدادات البرنامج'], 'program-settings', $settingsRoot->id);
        $clients             = $createModule(['en' => 'Clients',         'ar' => 'العملاء'], 'clients', $settingsRoot->id);
        $brokers             = $createModule(['en' => 'Brokers',         'ar' => 'الوسطاء'], 'brokers', $settingsRoot->id);

        $createFeature(['en' => 'Add Client', 'ar' => 'اضافة عميل'], 'add-client', $clients->id);
        $createFeature(['en' => 'Add Broker', 'ar' => 'اضافة وسيط'], 'add-broker', $brokers->id);

        /** Company Submodules */
        $companyProfile = $createModule(['en' => 'Company Profile', 'ar' => 'ملف الشركة'], 'company-profile', $companySettings->id);
        $officialData   = $createModule(['en' => 'Official Data', 'ar' => 'البيانات الرسمية'], 'official-data', $companyProfile->id);
        $createFeature(['en' => 'Branches', 'ar' => 'الفروع'], 'branches', $companyProfile->id);

        $officialFeatures = [
            ['name' => ['en' => 'Legal Info',         'ar' => 'البيانات القانونية'],   'slug' => 'legal-info'],
            ['name' => ['en' => 'Support Info',       'ar' => 'بيانات الدعم'],         'slug' => 'support-info'],
            ['name' => ['en' => 'National Address',   'ar' => 'العنوان الوطني'],       'slug' => 'national-address'],
            ['name' => ['en' => 'Official Documents', 'ar' => 'المستندات الرسمية'],   'slug' => 'official-documents'],
        ];

        foreach ($officialFeatures as $feature) {
            $createFeature($feature['name'], $feature['slug'], $officialData->id);
        }

        /** User Profile Settings Submodules */
        $userProfile       = $createModule(['en' => 'User Profile', 'ar' => 'الملف الشخصي'], 'user-profile', $userProfileSettings->id);
        $userContract      = $createModule(['en' => 'Work Contract', 'ar' => 'عقد العمل'], 'work-contract', $userProfileSettings->id);
        $attendancePolicy  = $createModule(['en' => 'Attendance Policy', 'ar' => 'سياسة الحضور'], 'attendance-policy', $userProfileSettings->id);
        $userProcedures = $createModule(['en' => 'User Procedures', 'ar' => 'إجراءات مستخدم'], 'user-procedures', $userProfileSettings->id);

        $createFeature(['en' => 'User Status',        'ar' => 'حالة المستخدم'],     'user-status', $userProcedures->id);
        $createFeature(['en' => 'SMS Messages',       'ar' => 'الرسائل النصية'],   'sms-messages', $userProcedures->id);
        $createFeature(['en' => 'Social Platforms',   'ar' => 'منصات التواصل'],    'social-platforms', $userProcedures->id);


        $userProfileFeatures = [
            ['name' => ['en' => 'Personal Data',      'ar' => 'البيانات الشخصية'],   'slug' => 'personal-data'],
            ['name' => ['en' => 'Professional Data',  'ar' => 'البيانات المهنية'],   'slug' => 'professional-data'],
            ['name' => ['en' => 'Bank Data',          'ar' => 'البيانات البنكية'],   'slug' => 'bank-data'],
            ['name' => ['en' => 'Activities',         'ar' => 'الأنشطة'],           'slug' => 'activities'],
            ['name' => ['en' => 'Activity Log',       'ar' => 'سجل النشاط'],        'slug' => 'activity-log'],
            ['name' => ['en' => 'Upcoming Meetings',  'ar' => 'الاجتماعات القادمة'],'slug' => 'upcoming-meetings'],
            ['name' => ['en' => 'Team',               'ar' => 'الفريق'],             'slug' => 'team'],
        ];

        foreach ($userProfileFeatures as $feature) {
            $createFeature($feature['name'], $feature['slug'], $userProfile->id);
        }

        /** Contract Submodules */
        $contractPersonal      = $createModule(['en' => 'Contract Personal Data', 'ar' => 'البيانات الشخصية'], 'contract-personal', $userContract->id);
        $academicExperience    = $createModule(['en' => 'Academic & Experience',  'ar' => 'البيانات الأكاديمية والخبرات'], 'academic-experience', $userContract->id);
        $jobContractual        = $createModule(['en' => 'Job & Contractual Data', 'ar' => 'البيانات الوظيفية والتعاقدية'], 'job-contract-data', $userContract->id);
        $financialPrivileges   = $createModule(['en' => 'Financial Privileges',   'ar' => 'الامتيازات المالية'], 'financial-privileges', $userContract->id);
        $contractManagement    = $createModule(['en' => 'Contract Management',    'ar' => 'إدارة العقد'], 'contract-management', $userContract->id);

        $contractPersonalFeatures = [
            ['name' => ['en' => 'Personal Info',      'ar' => 'البيانات الشخصية'],     'slug' => 'contract-personal-info'],
            ['name' => ['en' => 'Bank Info',          'ar' => 'المعلومات البنكية'],    'slug' => 'contract-bank-info'],
            ['name' => ['en' => 'Contact Info',       'ar' => 'معلومات الاتصال'],      'slug' => 'contract-contact-info'],
            ['name' => ['en' => 'Residence Info',     'ar' => 'معلومات الإقامة'],      'slug' => 'contract-residence-info'],
        ];

        foreach ($contractPersonalFeatures as $feature) {
            $createFeature($feature['name'], $feature['slug'], $contractPersonal->id);
        }

        $academicFeatures = [
            ['name' => ['en' => 'Qualification',           'ar' => 'المؤهل'], 'slug' => 'qualification'],
            ['name' => ['en' => 'Bio',                     'ar' => 'نبذة مختصرة'], 'slug' => 'bio'],
            ['name' => ['en' => 'Previous Experiences',    'ar' => 'الخبرات السابقة'], 'slug' => 'previous-experiences'],
            ['name' => ['en' => 'Training Courses',        'ar' => 'الكورسات التعليمية'], 'slug' => 'training-courses'],
            ['name' => ['en' => 'Professional Certificates','ar' => 'الشهادات المهنية'], 'slug' => 'professional-certificates'],
            ['name' => ['en' => 'CV',                      'ar' => 'السيرة الذاتية'], 'slug' => 'cv'],
        ];

        foreach ($academicFeatures as $feature) {
            $createFeature($feature['name'], $feature['slug'], $academicExperience->id);
        }

        $createFeature(['en' => 'Contractual Data', 'ar' => 'البيانات التعاقدية'], 'contractual-data', $jobContractual->id);
        $createFeature(['en' => 'Job Data',         'ar' => 'البيانات الوظيفية'],   'job-data', $jobContractual->id);

        $createFeature(['en' => 'Allowances',       'ar' => 'الامتيازات والبدلات'], 'allowances', $financialPrivileges->id);

        //TODO : integerate with permissions
    }
}
