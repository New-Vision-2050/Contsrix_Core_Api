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
                "name"=>"الموظفين",
                "slug" => "employees",

                'main_program_id' => Program::where("slug","human-resources")->first(),
                'is_active' => 1,
                'is_registrable' => 1,
                'default_attributes' => json_encode([
                    'name',
                    'email',
                    'phone',
                    'data_status',
                    'branch',
                    'job_title',
                ]),
                'optional_attributes' => json_encode([
                    'phone_code',
                    'user-type',
                    'companies',
                    'nickname',
                    'birthdate_gregorian',
                    'birthdate_hijri',
                    'nationality',
                    'residence',
                    'address',
                    'postal_code',
                    'landline_number',
                    'other_phone',
                    'marital-status',
                    'management',
                    'department',
                    'job_type',
                    'job_code',
                    'attendance_constraint',
                    'identity',
                    'passport',
                    'border_number',
                    'work_permit',
                    'whatsapp',
                    'linkedin',
                    'facebook',
                    'instagram',
                    'telegram',
                    'snapchat',
                    'currency',
                    'time_zone',
                    'language',
                    'bank-info',
                    'salary-info',
                    'employment-info',
                    'contact-info',
                    'social-media',
                    'family-info',
                    'about-me',
                    'cv',
                    'certificates',
                    'qualification',
                    'experience',
                    'courses',
                    'work-license',
                    'privileges',
                    'official-data',
                    'job-offer',
                    'contract-work',
                    'education',
                    'passport-info',
                    'residence-info',
                    'broker',
                    'number_of_projects',
                    'end_date',
                ]),

                'registration_form_id' => RegistrationForm::where("slug","employee")->first()
            ]
        )
            ->count(1)
            ->create();


    }
}
