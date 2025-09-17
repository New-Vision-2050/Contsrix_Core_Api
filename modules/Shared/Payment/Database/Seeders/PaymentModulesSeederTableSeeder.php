<?php

namespace Modules\Shared\Payment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shared\Payment\Models\Payment;
use Ranium\SeedOnce\Traits\SeedOnce;

class PaymentModulesSeederTableSeeder extends Seeder
{
    use SeedOnce;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $banks = [
            // Egypt Banks
            [
                'name' => ['en' => 'Paymob', 'ar' => 'باي موب'],
            ],
            [
                'name' => ['en' => 'Paypal', 'ar' => 'باي بال'],
            ],
            [
                'name' => ['en' => 'paytab', 'ar' => 'باي تاب'],
            ]
        ];

        foreach ($banks as $bank) {
            Payment::create(
                [
                    'name' => [
                        'en' => $bank['name']['en'],
                        'ar' => $bank['name']['ar'],
                    ],
                ]
            );
        }


    }

}
