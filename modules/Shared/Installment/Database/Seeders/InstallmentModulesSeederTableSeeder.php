<?php

namespace Modules\Shared\Installment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shared\Installment\Models\Installment;
use Ranium\SeedOnce\Traits\SeedOnce;

class InstallmentModulesSeederTableSeeder extends Seeder
{
    use SeedOnce;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $installmentProviders = [
            [
                'name' => ['en' => 'ValU', 'ar' => 'فاليو'],
            ],
            [
                'name' =>['en' => 'Souhoola', 'ar' => 'سهولة'],
            ],
            [
                'name' =>['en' => 'Contact', 'ar' => 'كونتكت'],
            ],
            [
                'name' =>['en' => 'BEE', 'ar' => 'بي'],
            ],
            [
                'name' =>['en' => 'Aman Installments', 'ar' => 'أمان للتقسيط'],
            ],
            [
                'name' =>['en' => 'Premium Card', 'ar' => 'بريميوم كارد'],
            ],
            [
                'name' =>['en' => 'Sympl', 'ar' => 'سيمبل'],
            ],
            [
                'name' => ['en' => 'Forsa', 'ar' => 'فرصة'],
            ],
            [
                'name' => ['en' => 'Shahry', 'ar' => 'شهري'],
            ],
        ];

        foreach ($installmentProviders as $provider) {
            Installment::create(
                [
                    'name' => [
                        'en' => $provider['name']['en'],
                        'ar' => $provider['name']['ar'],
                    ],
                ]
            );
        }
    }
}
