<?php

namespace Modules\Project\TermServices\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\TermServices\Models\TermServices;

class TermServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $termServices = [
            ['name' => 'معاملات'],
            ['name' => 'النسبه المئوية'],
            ['name' => 'مرفقات'],
            ['name' => 'مهمات'],
            ['name' => 'خطابات'],
            ['name' => 'فريق عمل'],
            ['name' => 'الشخص المسؤل'],
        ];

        foreach ($termServices as $service) {
            TermServices::firstOrCreate(
                ['name' => $service['name']],
                [
                    'name' => $service['name'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('TermServices seeded successfully with ' . count($termServices) . ' records.');
    }
}
