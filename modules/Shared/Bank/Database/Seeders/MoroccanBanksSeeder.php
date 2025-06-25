<?php

namespace Modules\Shared\Bank\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shared\Bank\Models\Bank;
use Ranium\SeedOnce\Traits\SeedOnce;

class MoroccanBanksSeeder extends Seeder
{
    use SeedOnce;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // A list of major Moroccan banks, based on Bank Al-Maghrib's regulated entities.
        // The country_id for Morocco is 149.
        $banks = [
            
            //== Major Commercial Banks (البنوك التجارية الكبرى) ==
            [
                'name' => ['en' => 'Attijariwafa bank', 'ar' => 'التجاري وفا بنك'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Groupe Banque Populaire (BCP)', 'ar' => 'مجموعة البنك الشعبي'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Bank of Africa (BOA)', 'ar' => 'بنك إفريقيا'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Crédit Agricole du Maroc (CAM)', 'ar' => 'القرض الفلاحي للمغرب'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'CIH Bank', 'ar' => 'بنك CIH'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Société Générale Maroc', 'ar' => 'الشركة العامة المغربية للأبناك'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'BMCI (BNP Paribas)', 'ar' => 'البنك المغربي للتجارة والصناعة'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Crédit du Maroc (CDM)', 'ar' => 'القرض العقاري والسياحي'],
                'country_id' => 149,
            ],

            //== Participatory Banks (البنوك التشاركية) ==
            [
                'name' => ['en' => 'Bank Assafa', 'ar' => 'بنك الصفاء'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Umnia Bank', 'ar' => 'أمنية بنك'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Bank Al Yousr', 'ar' => 'بنك اليسر'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'BTI Bank (Bank Al-Tamwil Wa Al-Inma)', 'ar' => 'بنك تمويل وإنماء'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Al Akhdar Bank', 'ar' => 'الأخضر بنك'],
                'country_id' => 149,
            ],

            //== Specialized & Other Banks (بنوك ومؤسسات أخرى) ==
            [
                'name' => ['en' => 'CFG Bank', 'ar' => 'بنك CFG'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Al Barid Bank', 'ar' => 'البريد بنك'],
                'country_id' => 149,
            ],
            // Note: CDG is a state investment arm, not a retail bank, so it's omitted for general use.
        ];

        foreach ($banks as $bank) {
            Bank::create([
                    'name' => [
                        'en' => $bank['name']['en'],
                        'ar' => $bank['name']['ar'],
                    ],
                    'country_id'=> $bank['country_id']
                ]);
        }
    }
}