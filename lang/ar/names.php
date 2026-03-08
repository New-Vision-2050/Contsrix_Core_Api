<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permission Names Language Lines (Arabic)
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for permission names
    |
    */

    // Common permission actions
    'create' => 'إنشاء',
    'read' => 'عرض',
    'update' => 'تعديل',
    'delete' => 'حذف',
    'list' => 'قائمة',
    'manage' => 'إدارة',
    'assign' => 'تعيين',
    'export' => 'تصدير',
    'import' => 'استيراد',
    'view' => 'عرض',
    'edit' => 'تعديل',
    'activate' => 'تفعيل',
    'login-as-admin' => 'تسجيل الدخول كمسؤول',
    'request-update' => 'طلب تحديث',
    "companies-list" => "عرض الشركات",
    "users-list" => "عرض المستخدمين",

    // Module specific names
    'user' => 'مستخدم',
    'users' => 'المستخدمين',
    'organization-list*users' => 'المستخدمين',
    'client' => 'عميل',

    'clients' => 'العملاء',
    'clients*clients' => 'العملاء',

    'broker' => 'وسيط',
    'brokers' => 'الوسطاء',
    'brokers*brokers' => 'الوسطاء',

    'employee' => 'موظف',
    'company' => 'شركة',
    'companies' => 'الشركات',
    'role' => 'دور',
    'roles' => 'الأدوار',
    'roles*roles' => 'الأدوار',
    'permission' => 'صلاحية',
    'permissions' => 'الصلاحيات',
    'permissions*permissions' => 'الصلاحيات',
    'identifier' => 'معرّف',
    'login-way' => 'طريقة تسجيل الدخول',
    'driver' => 'مزود الخدمة',
    'organization' => 'الهيكل التنظيمي',
    'organization-list' => ' الهيكل التنظيمي',
    'branch' => 'فرع',
    'organization-list*branch' => 'فرع',
    'management' => 'إدارة',
    'organization-list*management' => 'إدارة',
    'job-title' => 'المسمى الوظيفي',
    'job-type' => 'نوع الوظيفة',

    'organization-list*job-title' => 'المسمى الوظيفي',
    'organization-list*job-type' => 'نوع الوظيفة',
    'company-profile' => 'ملف الشركة',
    'company-profile*official-data' => 'البيانات الرسمية',
    'company-profile*legal-data' => 'البيانات القانونية',
    'company-profile*address' => 'العنوان',
    'company-profile*official-document' => 'وثيقة رسمية',
    'company-profile*branch' => 'وثيقة رسمية',
    "family-information"=>"معلومات العائلة",

    "human-resources"=>"الموارد البشرية",

    'attendance' => 'الحضور والانصراف',
    'attendance*attendance-list' => 'قائمة الحضور',
    'attendance*attendance-constraints' => 'محددات الحضور',
    'attendance-map' => 'خريطة الحضور',
    'attendance-list*attendance-map' => 'خريطة الحضور',
    "attendance-list" => "Attendance List",
    "attendance-constraints" => "محددات الحضور",
    "attendance-list*attendance-list" => "قائمة الحضور",
    "attendance-list*attendance-constraints" => "محددات الحضور",
    "attendance-constraints*attendance-constraints" => "محددات الحضور",

    // Settings module
    // SubEntity Module
    'sub-entity' => 'كيان فرعي',
    'users*sub-entity' => 'كيان فرعي',
    'sub-entities' => 'الكيانات الفرعية',
    'system' => 'النظام',
    'sub-entity-attributes' => 'خصائص الكيان الفرعي',
    'sub-entity-validation' => 'التحقق من الكيان الفرعي',
    'sub-entity-selection' => 'اختيار الكيان الفرعي',
    'super-entity' => 'كيان رئيسي',
    'users*super-entity' => 'كيان رئيسي',
    'super-entities' => 'الكيانات الرئيسية',
    'super-entity-attributes' => 'خصائص الكيان الرئيسي',
    'super-entity-registration' => 'تسجيل الكيان الرئيسي',
    'sub-entity-records' => 'سجلات الكيان الفرعي',

    // User Profile
    'user-profile' => 'الملف الشخصي للمستخدم',
    'user-profile*personal-information' => 'المعلومات الشخصية',
    'user-profile*contact-information' => 'معلومات الاتصال',
    'user-profile*passport-information' => 'معلومات جواز السفر',
    'user-profile*bank-information' => 'المعلومات البنكية',
    'user-profile*address-information' => 'معلومات العنوان',
    'user-profile*marital-status' => 'الحالة الاجتماعية',
    'user-profile*social-media-account' => 'حسابات التواصل الاجتماعي',
    'user-profile*border-number-information' => 'معلومات رقم الحدود',
    'user-profile*residence-information' => 'معلومات الإقامة',
    'user-profile*about-me-information' => 'معلومات عني',
    'user-profile*cv' => 'السيرة الذاتية',
    'user-profile*job-offer' => 'عرض عمل',
    'user-profile*contract-work' => 'عقد عمل',
    'user-profile*employment-information' => 'معلومات التوظيف',
    'user-profile*salary-information' => 'معلومات الراتب',
    'user-profile*qualification' => 'مؤهل',
    'user-profile*experience' => 'خبرة',
    'user-profile*courses' => 'دورات',
    'user-profile*certificates' => 'شهادات',
    'user-profile*work-license' => 'رخصة العمل',
    'user-profile*family-information' => 'معلومات العائلة',

    'user-profile*data' => 'معلومات الرسميه',
    'user-profile*education' => 'معلومات التعليم',
    'user-profile*privileges' => 'معلومات الامتيازات',
    'user-profile*identity' => 'معلومات الهويه',
    'user-profile*contact' => 'معلومات التواصل ',

    "program-settings"=>"اعدادات البرامج",

    // Geographic Data
    'country' => 'دولة',
    'state' => 'ولاية',
    'city' => 'مدينة',

    // Geographic Data
    'program-settings*country' => 'دولة',
    'program-settings*state' => 'ولاية',
    'program-settings*city' => 'مدينة',
    'program-settings*driver' => 'مشغل الخدمة',
    'program-settings*identifier' => 'المعرف',

    // User Profile generic
    'data' => 'بيانات',
    'contact' => 'اتصال',
    'identity' => 'هوية',

    // Module names
    'settings' => 'الإعدادات',
    'departments' => 'الأقسام',
    'organization-list*department' => 'الأقسام',
    'modules' => 'الوحدات',
    'features' => 'الميزات',
    'department' =>"قسم",

    // Additional terms from JSON response
    'package' => 'حزمة',
    'names.package' => 'حزمة',
    'company-access-program' => 'برنامج الوصول للشركة',
    'Brokers-3' => 'الوسطاء-3',
    'Brokers-3m' => 'الوسطاء-3م',
    'dynamic-brokers-3-2m' => 'الوسطاء الديناميكيون-3-2م',

    // User Profile detailed sections
    'user-profile*personal-info' => 'المعلومات الشخصية',
    'user-profile*family-info' => 'المعلومات العائلية',
    'user-profile*passport-info' => 'معلومات جواز السفر',

    'user-profile*social-media' => 'وسائل التواصل الاجتماعي',
    'user-profile*contact-info' => 'معلومات التواصل',
    'user-profile*bank-info' => 'المعلومات المصرفية',
    'user-profile*salary-info' => 'معلومات الراتب',
    'user-profile*employment-info' => 'معلومات التوظيف',
    'user-profile*residence-info' => 'معلومات الإقامة',
    'user-profile*border-number' => 'رقم الحدود',
    'user-profile*about-me' => 'عني',

    // Company Profile detailed sections


    // Program Settings detailed sections
    'program-settings*login-way' => 'طريقة تسجيل الدخول',


    // Organization list detailed sections

    "day"=> "يوم",

    "names.company-access-program"=> "برنامج الوصول للشركة",

    "legal-data"=> "البيانات القانونية",
    "official-document"=> "الوثائق الرسمية",
    "address"=> "العنوان",
    "family-info"=> "المعلومات العائلية",
    "certificates"=> "الشهادات",
    "passport-info"=> "معلومات جواز السفر",
    "qualification"=> "المؤهلات",
    "contract-work"=> "عقد العمل",
    "marital-status"=> "الحالة الاجتماعية",
    "education"=> "التعليم",
    "work-license"=> "رخصة العمل",
    "social-media"=> "وسائل التواصل الاجتماعي",
    "personal-info"=> "المعلومات الشخصية",
    "experience"=> "الخبرة",
    "cv"=> "السيرة الذاتية",
    "courses"=> "الدورات",
    "contact-info"=> "معلومات التواصل",
    "job-offer"=> "عرض العمل",
    "bank-info"=> "المعلومات المصرفية",
    "salary-info"=> "معلومات الراتب",
    "employment-info"=> "معلومات التوظيف",
    "residence-info"=> "معلومات الإقامة",
    "border-number"=> "رقم الحدود",
    "privileges"=> "الامتيازات",

    "about-me"=> "عني",
    "official-data"=> "البيانات الرسمية",

    // Missing user profile detailed information translations
    "passport-information" => "معلومات جواز السفر",
    "social-media-account" => "حساب وسائل التواصل الاجتماعي",
    "personal-information" => "المعلومات الشخصية",
    "contact-information" => "معلومات التواصل",
    "residence-information" => "معلومات الإقامة",
    "bank-information" => "المعلومات المصرفية",
    "employment-information" => "معلومات التوظيف",
    "salary-information" => "معلومات الراتب",
    "address-information" => "معلومات العنوان",
    "about-me-information" => "معلومات عني",
    "border-number-information" => "معلومات رقم الحدود",
    "program-and-packages" => "برامج وباقات",
    "user-list"=>"المستخدمين",
    "program-management" => "ادارة البرامج",
    "support-data" => "الدعم",
    "company-profile*support-data" => "الدعم",
    "leave-policy"=>"سياسة الاجازات",
    "settings*leave-policy"=>"سياسة الاجازات",
    "leave-type"=>"نوع الاجازات",
    "settings*leave-type"=>"نوع الاجازات",
    "public-holiday"=>"العطلات العامة",
    "settings*public-holiday"=>"العطلات العامة",
    "companies-list*companies-list"=>"الشركات",
    "user-list*users"=>"المستخدمين",
    "program-and-packages*package"=>"الباقات",
    "program-and-packages*company-access-program"=>"برنامج الوصول للشركة",

    // Client Dashboard Widgets
    "total_clients" => "اجمالي عدد العملاء",
    "clients_added_last_month" => "العملاء المضافين اخر شهر",
    "active_clients" => "العملاء النشطيين",
    "suspended_clients" => "العملاء المعلقين",

    // Broker Dashboard Widgets
    "total_brokers" => "اجمالي عدد الوسطاء",
    "brokers_added_last_month" => "الوسطاء المضافين اخر شهر",
    "active_brokers" => "الوسطاء النشطيين",
    "suspended_brokers" => "الوسطاء المعلقين",
    "client-relations"=>"علاقات العملاء",
    "audit-list"=>"السجل",
    "client-setting"=>"اعدادات العملاء",
    "archive-library"=>"المستندات و البيانات",
    "folder"=>"المجلدات",
    "file"=>"الملفات",
    "log"=>"سجل الانشطة",
    
    // Client Relations Module
    "client-requests"=>"طلبات العملاء",
    "client-relations*client-requests"=>"طلبات العملاء",
    "client-setting*term-service-settings"=>"اعدادات شروط الخدمة",
    "client-setting*term-setting"=>"اعدادات الشروط",
    
    // Work Panel Module
    "work-panel"=>"لوحة العمل",
    "project-management"=>"ادارة المشاريع",
    "work-panel*project-management"=>"ادارة المشاريع",
    "project-settings"=>"اعدادات المشاريع",
    "work-panel*project-settings"=>"اعدادات المشاريع",
    "project-type"=>"نوع المشروع",
    "term-setting"=>"اعدادات الشروط",
    "term-service-settings"=>"اعدادات شروط الخدمة",

    // ================================================================================================
    // WEBSITE CMS MODULE TRANSLATIONS - ترجمات نظام إدارة محتوى الموقع
    // ================================================================================================

    // Website CMS Main Module
    'website-cms' => 'الملف التعريفي',

    // Website About Us
    'website-about-us' => 'من نحن',
    'website-cms*website-about-us' => 'من نحن',

    // Website Address
    'website-address' => 'عنوان الموقع',
    'website-cms*website-address' => 'عنوان الموقع',

    // Website Contact Info
    'website-contact-info' => 'معلومات التواصل',
    'website-cms*website-contact-info' => 'معلومات التواصل',

    // Category Website CMS
    'category-website-cms' => 'تصنيفات الموقع',
    'website-cms*category-website-cms' => 'تصنيفات الموقع',

    // Founder
    'founder' => 'المؤسسون',
    'website-cms*founder' => 'المؤسسون',

    // Social Media Link
    'social-media-link' => 'روابط التواصل الاجتماعي',
    'website-cms*social-media-link' => 'روابط التواصل الاجتماعي',

    // Website Home Page Setting
    'website-home-page-setting' => 'إعدادات الصفحة الرئيسية',
    'website-cms*website-home-page-setting' => 'إعدادات الصفحة الرئيسية',

    // Website Theme Setting
    'website-theme' => 'إعدادات الملف',
    'website-cms*website-theme' => 'إعدادات الملف',

    // Website Icon
    'website-icon' => 'أيقونات الموقع',
    'website-cms*website-icon' => 'أيقونات الموقع',

    // Website News
    'website-news' => 'أخبار الموقع',
    'website-cms*website-news' => 'أخبار الموقع',

    // Website Our Service
    'website-our-service' => 'خدماتنا',
    'website-cms*website-our-service' => 'خدماتنا',

    // Website Project
    'website-project' => 'مشاريع الموقع',
    'website-cms*website-project' => 'مشاريع الموقع',

    // Website Project Setting
    'website-project-setting' => 'إعدادات المشاريع',
    'website-cms*website-project-setting' => 'إعدادات المشاريع',

    // Website Service
    'website-service' => 'خدمات الموقع',
    'website-cms*website-service' => 'خدمات الموقع',

    // Website Term And Condition
    'website-term-and-condition' => 'الشروط والأحكام',
    'website-cms*website-term-and-condition' => 'الشروط والأحكام',

    "website-theme-setting"=>"اعدادات الثيمات",
    "website-theme-setting*website-theme-setting"=>"اعدادات الثيمات",

    // ================================================================================================
    // ECOMMERCE MODULE TRANSLATIONS - ترجمات نظام التجارة الإلكترونية
    // ================================================================================================

    // E-Commerce Main Module
    'ecommerce' => 'التجارة الإلكترونية',

    // Banner
    'banner' => 'البانر',
    'ecommerce.banner*banner' => 'البانر',
    'banner*banner' => 'البانر',

    // Setting Page
    'setting-page' => 'صفحة الإعدادات',
    'ecommerce.setting-page*setting-page' => 'صفحة الإعدادات',
    'setting-page*setting-page' => 'صفحة الإعدادات',

    // Feature
    'feature' => 'الميزة',
    'ecommerce.feature*feature' => 'الميزة',
    'feature*feature' => 'الميزة',

    // Store Branch
    'store-branch' => 'فرع المتجر',
    'ecommerce.store-branch*store-branch' => 'فرع المتجر',
    'store-branch*store-branch' => 'فرع المتجر',

    // Dashboard
    'dashboard' => 'لوحة التحكم',
    'ecommerce.dashboard*dashboard' => 'لوحة التحكم',
    'dashboard*dashboard' => 'لوحة التحكم',
    'orders-chart' => 'رسم بياني للطلبات',
    'warehouses-table' => 'جدول المستودعات',

    // Coupon
    'coupon' => 'كوبون',
    'ecommerce.coupon*coupon' => 'كوبون',
    'coupon*coupon' => 'كوبون',

    // Deal Day
    'deal-day' => 'عرض اليوم',
    'ecommerce.deal-day*deal-day' => 'عرض اليوم',
    'deal-day*deal-day' => 'عرض اليوم',

    // Eco Brand
    'eco-brand' => 'العلامة التجارية',
    'ecommerce.eco-brand*eco-brand' => 'العلامة التجارية',
    'eco-brand*eco-brand' => 'العلامة التجارية',

    // Category
    'category' => 'التصنيف',
    'ecommerce.category*category' => 'التصنيف',
    'category*category' => 'التصنيف',

    // Product
    'product' => 'المنتج',
    'ecommerce.product*product' => 'المنتج',
    'product*product' => 'المنتج',

    // Feature Deal
    'feature-deal' => 'عرض مميز',
    'ecommerce.feature-deal*feature-deal' => 'عرض مميز',
    'feature-deal*feature-deal' => 'عرض مميز',

    // Flash Deal
    'flash-deal' => 'عرض سريع',
    'ecommerce.flash-deal*flash-deal' => 'عرض سريع',
    'flash-deal*flash-deal' => 'عرض سريع',

    // Order
    'order' => 'الطلب',
    'ecommerce.order*order' => 'الطلب',
    'order*order' => 'الطلب',
    'update-status' => 'تحديث الحالة',

    // Social Media
    'social-media' => 'وسائل التواصل',
    'ecommerce.social-media*social-media' => 'وسائل التواصل',
    'social-media*social-media' => 'وسائل التواصل',

    // Warehouse
    'warehouse' => 'المستودع',
    'ecommerce.warehouse*warehouse' => 'المستودع',
    'warehouse*warehouse' => 'المستودع',

    // Page
    'page' => 'الصفحة',
    'ecommerce.page*page' => 'الصفحة',
    'page*page' => 'الصفحة',

    // Payment Method
    'payment-method' => 'طريقة الدفع',
    'ecommerce.payment-method*payment-method' => 'طريقة الدفع',
    'payment-method*payment-method' => 'طريقة الدفع',

];
