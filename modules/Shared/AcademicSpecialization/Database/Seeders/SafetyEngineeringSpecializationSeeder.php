<?php

namespace Modules\Shared\AcademicSpecialization\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;
use Ranium\SeedOnce\Traits\SeedOnce;

class SafetyEngineeringSpecializationSeeder extends Seeder
{
    use SeedOnce;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $specialization = [
            'ar' => 'هندسة الأمن والسلامة',
            'en' => 'Safety and Security Engineering',
            'code' => 'engineering-safety'
        ];

        // Get the max ID and add 1 to avoid conflicts
        $maxId = (int) (AcademicSpecialization::max('id') ?? 0);
        $newId = $maxId + 1;

        AcademicSpecialization::firstOrCreate(
            ['id' => $newId],
            ['name' => ['en' => $specialization['en'], 'ar' => $specialization['ar']], 'code' => $specialization['code']]
        );

        $this->command->info('✅ Safety and Security Engineering specialization added successfully!');
    }
}
