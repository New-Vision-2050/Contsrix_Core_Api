<?php

namespace Modules\Shared\AcademicSpecialization\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\AcademicQualification\Models\AcademicQualification;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;
use Ranium\SeedOnce\Traits\SeedOnce;

class AcademicSpecializationsUpdateSeederTableSeeder extends Seeder
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

        $bachelorId = AcademicQualification::whereHas('translations', function ($q) {
            $q->where('content', 'Bachelor');
        })->value('id');

        // Update all rows in one query
        AcademicSpecialization::query()->update([
            'academic_qualification_id' => $bachelorId,
        ]);

    }
}
