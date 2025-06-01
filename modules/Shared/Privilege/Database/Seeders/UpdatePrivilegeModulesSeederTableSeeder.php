<?php

namespace Modules\Shared\Privilege\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shared\Privilege\Models\Privilege;
use Ranium\SeedOnce\Traits\SeedOnce;

class UpdatePrivilegeModulesSeederTableSeeder extends Seeder
{
    use SeedOnce;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {

        $privileges = [
            [
                'name' => ['en' => 'Housing Allowance', 'ar' => 'بدل سكن'],
                'type' => 'housing_allowance',
            ],
            [
                'name' => ['en' => 'Flight reservation', 'ar' => 'حجز طيران'],
                'type' =>"flight_reservation" ,
            ],
            [
                'name' => ['en' => 'Health Insurance', 'ar' => 'تأمين طبي'],
                'type' => 'health_insurance',
            ],
            [
                'name' => ['en' => 'Social Insurance', 'ar' => 'تأمين اجتماعي'],
                'type' => 'social_insurance',
            ],
            [
                'name' => ['en' => 'Car Allowance', 'ar' => 'بدل سيارة'],
                'type' =>'car_allowance' ,
            ],
            [
                'name' => ['en' => 'Telecommunications Allowance', 'ar' => 'بدل اتصالات'],
                'type' => 'telecommunications_allowance',
            ],

        ];

        foreach ($privileges as $privilege) {
            Privilege::updateOrCreate(
                ['type' => $privilege['type']],
                [
                    'name' => [
                        'en' => $privilege['name']['en'],
                        'ar' => $privilege['name']['ar'],
                    ],
                ]
            );
        }

    }

}
