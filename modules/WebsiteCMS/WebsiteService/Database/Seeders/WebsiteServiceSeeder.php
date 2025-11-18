<?php

namespace Modules\WebsiteCMS\WebsiteService\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Models\CategoryWebsiteCMS;
use Modules\WebsiteCMS\WebsiteService\Models\WebsiteService;

class WebsiteServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Get first category or create one if doesn't exist
        $category = CategoryWebsiteCMS::first();

        if (!$category) {
            $category = CategoryWebsiteCMS::create([
                'name' => [
                    'ar' => 'خدمات',
                    'en' => 'Services',
                ],
                'category_type' => 1,
                'company_id' => tenant('id'),
            ]);
        }

        $services = [
            [
                'name' => [
                    'ar' => 'تطوير المواقع',
                    'en' => 'Web Development',
                ],
                'description' => [
                    'ar' => 'نقدم خدمات تطوير المواقع الإلكترونية باستخدام أحدث التقنيات',
                    'en' => 'We provide web development services using the latest technologies',
                ],
                'reference_number' => 'WS-001',
            ],
            [
                'name' => [
                    'ar' => 'تطوير تطبيقات الجوال',
                    'en' => 'Mobile App Development',
                ],
                'description' => [
                    'ar' => 'تطوير تطبيقات الجوال لأنظمة iOS و Android',
                    'en' => 'Mobile app development for iOS and Android systems',
                ],
                'reference_number' => 'WS-002',
            ],
            [
                'name' => [
                    'ar' => 'التسويق الرقمي',
                    'en' => 'Digital Marketing',
                ],
                'description' => [
                    'ar' => 'خدمات التسويق الرقمي وإدارة وسائل التواصل الاجتماعي',
                    'en' => 'Digital marketing services and social media management',
                ],
                'reference_number' => 'WS-003',
            ],
        ];

        foreach ($services as $serviceData) {
            WebsiteService::create([
                'name' => $serviceData['name'],
                'description' => $serviceData['description'],
                'reference_number' => $serviceData['reference_number'],
                'category_website_cms_id' => $category->id,
                'company_id' => tenant('id'),
            ]);
        }
    }
}
