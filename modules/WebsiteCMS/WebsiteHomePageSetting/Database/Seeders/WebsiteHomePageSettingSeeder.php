<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Models\WebsiteHomePageSetting;
use Illuminate\Support\Facades\DB;

class WebsiteHomePageSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = tenant('id');

        // Check if setting already exists for this company
        $existingSetting = WebsiteHomePageSetting::where('company_id', $companyId)->first();

        if ($existingSetting) {
            return;
        }

        // Create default setting
        WebsiteHomePageSetting::create([
            'company_id' => $companyId,
            'web_video_link' => '',
            'mobile_video_link' => '',
            'description' => [
                'ar' => 'وصف الصفحة الرئيسية الافتراضي',
                'en' => 'Default home page description',
            ],
            'is_companies' => true,
            'is_approvals' => true,
            'is_certificates' => true,
            'status' => 1,
        ]);
    }
}
