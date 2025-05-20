<?php

declare(strict_types=1);

namespace Modules\SubEntity\Database\Seeders;

use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\SubEntity\Models\RegistrationForm;

class RegistrationFormsSeeder extends Seeder
{
    use SeedOnce;
    public function run(): void
    {
        Model::unguard();

        $registrationForms = [
            [
                "name" => [
                    'ar' => 'الموظفين',
                    'en' => 'Employees'
                ],
                'slug' => 'employee',
                'company_user_role_map' => CompanyUserRole::EMPLOYEE->value
            ],
            [
                "name" => [
                    'ar' => 'العملاء',
                    'en' => 'Clients'
                ],
                'slug' => 'client',
                'company_user_role_map' => CompanyUserRole::CLIENT->value
            ],
            [
                "name" => [
                    'ar' => 'الوسطاء',
                    'en' => 'Brokers'
                ],
                'slug' => 'broker',
                'company_user_role_map' => CompanyUserRole::BROKER->value
            ]
        ];

        foreach ($registrationForms as $form) {
            RegistrationForm::updateOrCreate(
                ['slug' => $form['slug']],
                ['name' => $form['name'], 'company_user_role_map' => $form['company_user_role_map']],
            );
        }
    }
}
