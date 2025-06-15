<?php

namespace Modules\Shared\Bank\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shared\Bank\Models\Bank;
use Ranium\SeedOnce\Traits\SeedOnce;

class BanksModulesSeederTableSeeder extends Seeder
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
                'name' => ['en' => 'National Bank of Egypt', 'ar' => 'البنك الأهلي المصري'],
                'country_id' => 65,
            ],
            [
                'name' => ['en' => 'Banque Misr', 'ar' => 'بنك مصر'],
                'country_id' => 65,
            ],
            [
                'name' => ['en' => 'Banque du Caire', 'ar' => 'بنك القاهرة'],
                'country_id' => 65,
            ],
            [
                'name' => ['en' => 'Bank of Alexandria', 'ar' => 'بنك الإسكندرية'],
                'country_id' => 65,
            ],
            [
                'name' => ['en' => 'Agricultural Bank of Egypt', 'ar' => 'البنك الزراعي المصري'],
                'country_id' => 65,
            ],
            [
                'name' => ['en' => 'Industrial Development Bank of Egypt', 'ar' => 'بنك التنمية الصناعية'],
                'country_id' => 65,
            ],
            [
                'name' => ['en' => 'The United Bank', 'ar' => 'المصرف المتحد'],
                'country_id' => 65,
            ],
            [
                'name' => ['en' => 'Housing and Development Bank', 'ar' => 'بنك التعمير والإسكان'],
                'country_id' => 65,
            ],
            [
                'name' => ['en' => 'Export Development Bank of Egypt', 'ar' => 'البنك المصري لتنمية الصادرات'],
                'country_id' => 65,
            ],
            [
                'name' => ['en' => 'Commercial International Bank (CIB)', 'ar' => 'البنك التجاري الدولي'],
                'country_id' => 65,
            ],

            // Saudi Arabia Banks
            [
                'name' => ['en' => 'Saudi National Bank (SNB)', 'ar' => 'البنك الأهلي السعودي'],
                'country_id' => 194,
            ],
            [
                'name' => ['en' => 'Saudi Awwal Bank (SAB)', 'ar' => 'البنك السعودي الأول'],
                'country_id' => 194,
            ],
            [
                'name' => ['en' => 'The Saudi Investment Bank (SAIB)', 'ar' => 'البنك السعودي للاستثمار'],
                'country_id' => 194,
            ],
            [
                'name' => ['en' => 'Alinma Bank', 'ar' => 'مصرف الإنماء'],
                'country_id' => 194,
            ],
            [
                'name' => ['en' => 'Banque Saudi Fransi (BSF)', 'ar' => 'البنك السعودي الفرنسي'],
                'country_id' => 194,
            ],
            [
                'name' => ['en' => 'Riyad Bank', 'ar' => 'بنك الرياض'],
                'country_id' => 194,
            ],
            [
                'name' => ['en' => 'Alrajhi Bank', 'ar' => 'مصرف الراجحي'],
                'country_id' => 194,
            ],
            [
                'name' => ['en' => 'Arab National Bank (ANB)', 'ar' => 'البنك العربي الوطني'],
                'country_id' => 194,
            ],
            [
                'name' => ['en' => 'Bank AlBilad', 'ar' => 'بنك البلاد'],
                'country_id' => 194,
            ],
            [
                'name' => ['en' => 'Bank Aljazira', 'ar' => 'بنك الجزيرة'],
                'country_id' => 194,
            ],
        ];

        foreach ($banks as $bank) {
            Bank::create(
                [
                    'name' => [
                        'en' => $bank['name']['en'], // English name
                        'ar' => $bank['name']['ar'], // Arabic name
                    ],
                    'country_id' => $bank['country_id'],
                ]
            );
        }


    }

}
