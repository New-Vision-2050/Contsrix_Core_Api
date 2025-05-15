<?php

declare(strict_types=1);

namespace Modules\SubEntity\Database\Seeders;

use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;
use Illuminate\Database\Eloquent\Model;
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
                'slug' => 'employee'
            ],
            [
                "name" => [
                    'ar' => 'العملاء',
                    'en' => 'Customers'
                ],
                'slug' => 'customer'
            ],
            [
                "name" => [
                    'ar' => 'الوسطاء',
                    'en' => 'Resellers'
                ],
                'slug' => 'reseller'
            ]
        ];

        foreach ($registrationForms as $form) {
            RegistrationForm::firstOrCreate(
                ['slug' => $form['slug']],
                ['name' => $form['name']],
            );
        }
    }
}
