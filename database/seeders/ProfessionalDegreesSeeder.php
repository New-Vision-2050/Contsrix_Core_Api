<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ranium\SeedOnce\Traits\SeedOnce;

class ProfessionalDegreesSeeder extends Seeder
{
    use SeedOnce;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $professionalDegrees = [
            ['name_ar' => 'مهندس', 'name_en' => 'Engineer', 'is_active' => true],
            ['name_ar' => 'مهندس استشاري', 'name_en' => 'Consulting Engineer', 'is_active' => true],
            ['name_ar' => 'مهندس معترف', 'name_en' => 'Recognized Engineer', 'is_active' => true],
            ['name_ar' => 'فني', 'name_en' => 'Technician', 'is_active' => true],
            ['name_ar' => 'إجتماعي', 'name_en' => 'Social', 'is_active' => true],
        ];

        foreach ($professionalDegrees as $degree) {
            DB::table('professional_degrees')->insertOrIgnore([
                'name_ar' => $degree['name_ar'],
                'name_en' => $degree['name_en'],
                'is_active' => $degree['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
