<?php

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\WebsiteCMS\WebsiteSetting\Models\WebsiteSetting;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Models\WebsiteTermAndCondition;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Presenters\WebsiteTermAndConditionPresenter;

class WebsiteTermsAndConditionSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $companyId = tenant('id');

        if (!$companyId) {
            $this->command->warn('No tenant company_id found. Skipping WebsiteSetting seeder.');
            return;
        }

        WebsiteTermAndCondition::firstOrCreate(
            [
                'company_id' => $companyId,
            ],
            [
                "company_id" => $companyId,
                "content" => "",
            ]
        );

        $this->command->info('WebsiteSetting seeded successfully for company_id: ' . $companyId);
    }
}
