<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Leave\LeavePolicy\Models\LeavePolicy;
use Ramsey\Uuid\Uuid;

class LeavePolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates a default Annual Year leave policy with 21 days for each company.
     */
    public function run(): void
    {
        $companyId = tenant('id') ?? '560005d6-04b8-53b3-9889-d312648288e3';
        // Check if Annual Year policy already exists for this tenant
        $existingPolicy = LeavePolicy::where('name', 'Annual Year')
            ->where('company_id', $companyId)
            ->first();

        // Only create if it doesn't already exist
        if (!$existingPolicy) {
            LeavePolicy::create([
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Annual Year',
                'total_days' => 21,
                'day_type' => 'Annual',
                'is_rollover_allowed' => true,
                'max_days_per_request' => 21,
                'upgrade_condition' => null,
                'is_allow_half_day' => true,
                'company_id' => $companyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✅ Default Annual Year leave policy (21 days) created for company: ' . tenant('id'));
        } else {
            $this->command->info('ℹ️ Annual Year leave policy already exists for company: ' . tenant('id'));
        }
    }
}
