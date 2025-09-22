<?php

namespace Modules\Ecommerce\EcoAppSetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EcoFilterSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = 'your-company-uuid-here'; // Replace with actual company ID
        
        $filters = [
            [
                'id' => Str::uuid(),
                'company_id' => $companyId,
                'filter_name' => 'الجديد',
                'filter_key' => 'newest',
                'filter_type' => 'sort',
                'is_active' => true,
                'sort_order' => 1,
                'show_in_app' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $companyId,
                'filter_name' => 'المفضلة',
                'filter_key' => 'featured',
                'filter_type' => 'filter',
                'is_active' => true,
                'sort_order' => 2,
                'show_in_app' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $companyId,
                'filter_name' => 'الأقل سعرا',
                'filter_key' => 'price_low_high',
                'filter_type' => 'sort',
                'is_active' => true,
                'sort_order' => 3,
                'show_in_app' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $companyId,
                'filter_name' => 'الأعلى سعرا',
                'filter_key' => 'price_high_low',
                'filter_type' => 'sort',
                'is_active' => true,
                'sort_order' => 4,
                'show_in_app' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('eco_filter_settings')->insert($filters);
    }
}
