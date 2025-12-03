<?php

namespace Modules\WebsiteCMS\WebsiteContactInfo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\WebsiteCMS\WebsiteContactInfo\Models\WebsiteContactInfo;

class WebsiteContactInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = tenant('id');

        if (!$companyId) {
            $this->command->error('No tenant context found. This seeder must run in tenant context.');
            return;
        }

        // Check if contact info already exists for this company
        $existingContactInfo = WebsiteContactInfo::where('company_id', $companyId)->first();

        if ($existingContactInfo) {
            $this->command->info("Contact info already exists for company: {$companyId}");
            return;
        }

        // Create default contact info for the company
        WebsiteContactInfo::create([
            'company_id' => $companyId,
            'email' => '',
            'phone' => '',
        ]);

        $this->command->info("Contact info created successfully for company: {$companyId}");
    }
}
