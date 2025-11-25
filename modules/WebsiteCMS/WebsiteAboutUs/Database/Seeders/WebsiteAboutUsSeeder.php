<?php

namespace Modules\WebsiteCMS\WebsiteAboutUs\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\WebsiteCMS\WebsiteAboutUs\Models\WebsiteAboutUs;

class WebsiteAboutUsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = tenant('id');

        // Check if about us already exists for this company
        $exists = WebsiteAboutUs::where('company_id', $companyId)->exists();

        if ($exists) {
            $this->command->info('WebsiteAboutUs already exists for this company.');
            return;
        }

        // Create default about us for the company
        $aboutUs = WebsiteAboutUs::create([
            'company_id' => $companyId,
            'title' =>"من نحن",
            'description' => 'نحن شركة رائدة في مجال تقديم الخدمات المتميزة',
            'is_certificates' => true,
            'is_approvals' => true,
            'is_companies' => false,
            'about_me' => [
                'ar' => 'تأسست شركتنا بهدف تقديم أفضل الخدمات والحلول المبتكرة لعملائنا. نحن نؤمن بالجودة والتميز في كل ما نقدمه.',
                'en' => 'Our company was founded with the goal of providing the best services and innovative solutions to our clients. We believe in quality and excellence in everything we offer.',
            ],
            'vision' => [
                'ar' => 'أن نكون الخيار الأول والأفضل في مجال عملنا على المستوى المحلي والإقليمي',
                'en' => 'To be the first and best choice in our field at the local and regional level',
            ],
            'target' => [
                'ar' => 'تحقيق رضا العملاء من خلال تقديم خدمات عالية الجودة وحلول مبتكرة',
                'en' => 'Achieving customer satisfaction by providing high-quality services and innovative solutions',
            ],
            'slogan' => [
                'ar' => 'التميز في الخدمة والجودة في الأداء',
                'en' => 'Excellence in service and quality in performance',
            ],
            'status' => 1,
        ]);

        // Create sample project types
        $aboutUs->projectTypes()->createMany([
            [
                'title' => [
                    'ar' => 'مشاريع سكنية',
                    'en' => 'Residential Projects',
                ],
                'count' => 50,
            ],
            [
                'title' => [
                    'ar' => 'مشاريع تجارية',
                    'en' => 'Commercial Projects',
                ],
                'count' => 30,
            ],
            [
                'title' => [
                    'ar' => 'مشاريع صناعية',
                    'en' => 'Industrial Projects',
                ],
                'count' => 20,
            ],
        ]);

        $this->command->info('WebsiteAboutUs created successfully for company: ' . $companyId);
    }
}
