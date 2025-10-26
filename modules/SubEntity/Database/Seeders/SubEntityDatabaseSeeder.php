<?php

declare(strict_types=1);

namespace Modules\SubEntity\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Program\Models\Program;
use Modules\SubEntity\Models\RegistrationForm;
use Ranium\SeedOnce\Traits\SeedOnce;
use Modules\SubEntity\Models\SubEntity;

class SubEntityDatabaseSeeder extends Seeder
{
    use SeedOnce;

    public function run(): void
    {
        $superEntityType = fake()->randomElement(['users']);

        SubEntity::factory([
                'super_entity' => $superEntityType,
                'origin_super_entity' => $superEntityType,

                'main_program_id' => Program::where("slug","human-resources")->first(),
                'is_active' => 1,
                'is_registrable' => 1,
                'default_attributes' => json_encode([
                    'name',
                    'email',
                    'phone',
                ]),

                'registration_form_id' => RegistrationForm::where("slug","employee")->first()
            ]
        )
            ->count(1)
            ->create();


    }
}
