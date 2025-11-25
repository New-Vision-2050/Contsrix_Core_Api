<?php

namespace Modules\WebsiteCMS\WebsiteOurService\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\WebsiteCMS\WebsiteOurService\Models\WebsiteOurService;
use Modules\WebsiteCMS\WebsiteOurService\Enums\ServiceTypeEnum;
use Modules\WebsiteCMS\WebsiteService\Models\WebsiteService;

class WebsiteOurServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = tenant('id');

        // Check if website_our_service already exists for this company
        $existingService = WebsiteOurService::where('company_id', $companyId)->first();

        if ($existingService) {
            return; // Already seeded for this company
        }

        // Get some website services for the company to use in departments
        $websiteServices = WebsiteService::where('company_id', $companyId)
            ->limit(10)
            ->pluck('id')
            ->toArray();

        if (empty($websiteServices)) {
            // No website services available, skip seeding
            return;
        }

        // Create the main website our service
        $websiteOurService = WebsiteOurService::create([
            'title' => 'خدماتنا',
            'description' => 'نقدم مجموعة متنوعة من الخدمات المتميزة',
            'company_id' => $companyId,
            'status' => 1,
        ]);

        // Create departments with different types

        // Department 1: Cards type (can have any number of services)
        if (count($websiteServices) >= 3) {
            $department1 = $websiteOurService->departments()->create([
                'title' => [
                    'ar' => 'قسم التصميم والإبداع',
                    'en' => 'Design and Creativity Department',
                ],
                'description' => [
                    'ar' => 'نقدم خدمات التصميم الإبداعي والمبتكر',
                    'en' => 'We provide creative and innovative design services',
                ],
                'type' => ServiceTypeEnum::CARDS->value,
            ]);

            // Attach 3 services to this department
            $department1->websiteServices()->attach(array_slice($websiteServices, 0, 3));
        }

        // Department 2: Hexa type (must have exactly 6 services)
        if (count($websiteServices) >= 6) {
            $department2 = $websiteOurService->departments()->create([
                'title' => [
                    'ar' => 'قسم التطوير والبرمجة',
                    'en' => 'Development and Programming Department',
                ],
                'description' => [
                    'ar' => 'نقدم حلول تطوير برمجية متكاملة',
                    'en' => 'We provide comprehensive software development solutions',
                ],
                'type' => ServiceTypeEnum::HEXA->value,
            ]);

            // Attach exactly 6 services to this department
            $department2->websiteServices()->attach(array_slice($websiteServices, 0, 6));
        }

        // Department 3: Another Cards type
        if (count($websiteServices) >= 4) {
            $department3 = $websiteOurService->departments()->create([
                'title' => [
                    'ar' => 'قسم التسويق الرقمي',
                    'en' => 'Digital Marketing Department',
                ],
                'description' => [
                    'ar' => 'نقدم استراتيجيات تسويق رقمي فعالة',
                    'en' => 'We provide effective digital marketing strategies',
                ],
                'type' => ServiceTypeEnum::CARDS->value,
            ]);

            // Attach 4 services to this department
            $department3->websiteServices()->attach(array_slice($websiteServices, 0, 4));
        }
    }
}
