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
//        SubEntity::factory([
//                'super_entity' => $superEntityType,
//                'origin_super_entity' => $superEntityType,
//
//                'main_program_id' => Program::where("slug","human-resources")->first(),
//                'is_active' => 1,// 80% chance of being active
//                'is_registrable' => 1, // 30% chance of being registrable
//                'default_attributes' => json_encode([
//                    'name',
//                    'email',
//                    'phone',
//                ]),
//                'optional_attributes' => $this->faker->optional()->passthrough(json_encode([
//                    "company_id",
//                    "is_owner",
//                    "management_hierarchy_id"
//                ])),
//                'registration_form_id' => RegistrationForm::first()
//            ]
//        )
//            ->count(1)
//            ->create();


    }
}
