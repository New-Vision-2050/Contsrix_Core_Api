<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\WebsiteCMS\WebsiteSetting\Models\WebsiteSetting;

class WebsiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = tenant('id');

        if (!$companyId) {
            $this->command->warn('No tenant company_id found. Skipping WebsiteSetting seeder.');
            return;
        }

        WebsiteSetting::firstOrCreate(
            [
                'company_id' => $companyId,
            ],
            [
                "company_id" => $companyId,
            ]
        );

        $this->command->info('WebsiteSetting seeded successfully for company_id: ' . $companyId);
    }
}
