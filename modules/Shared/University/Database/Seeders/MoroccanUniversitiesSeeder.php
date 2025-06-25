<?php

namespace Modules\Shared\University\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shared\University\Models\University;
use Ranium\SeedOnce\Traits\SeedOnce;

class MoroccanUniversitiesSeeder extends Seeder
{
    use SeedOnce;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // The country_id for Morocco is 149.
        $universities = [

            //== Public Universities (الجامعات العمومية) ==
            [
                'name' => ['en' => 'Mohammed V University of Rabat', 'ar' => 'جامعة محمد الخامس بالرباط'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Hassan II University of Casablanca', 'ar' => 'جامعة الحسن الثاني بالدار البيضاء'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Sidi Mohamed Ben Abdellah University', 'ar' => 'جامعة سيدي محمد بن عبد الله بفاس'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Cadi Ayyad University', 'ar' => 'جامعة القاضي عياض بمراكش'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Abdelmalek Essaâdi University', 'ar' => 'جامعة عبد المالك السعدي بتطوان'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Ibn Zohr University', 'ar' => 'جامعة ابن زهر بأكادير'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Moulay Ismail University', 'ar' => 'جامعة مولاي إسماعيل بمكناس'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Mohammed I University', 'ar' => 'جامعة محمد الأول بوجدة'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Ibn Tofail University', 'ar' => 'جامعة ابن طفيل بالقنيطرة'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Hassan I University', 'ar' => 'جامعة الحسن الأول بسطات'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Sultan Moulay Slimane University', 'ar' => 'جامعة السلطان مولاي سليمان ببني ملال'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Chouaib Doukkali University', 'ar' => 'جامعة شعيب الدكالي بالجديدة'],
                'country_id' => 149,
            ],

            //== Grandes Écoles & Specialized Institutes (المدارس العليا والمعاهد) ==
            [
                'name' => ['en' => 'Mohammadia School of Engineers (EMI)', 'ar' => 'المدرسة المحمدية للمهندسين'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Hassania School of Public Works (EHTP)', 'ar' => 'المدرسة الحسنية للأشغال العمومية'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'National Institute of Posts and Telecommunications (INPT)', 'ar' => 'المعهد الوطني للبريد والمواصلات'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'National Institute of Statistics and Applied Economics (INSEA)', 'ar' => 'المعهد الوطني للإحصاء والاقتصاد التطبيقي'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'National School of Computer Science and Systems Analysis (ENSIAS)', 'ar' => 'المدرسة الوطنية العليا للمعلوميات وتحليل النظم'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'National Schools of Applied Sciences (ENSA Network)', 'ar' => 'شبكة المدارس الوطنية للعلوم التطبيقية'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Higher Institute of Commerce and Business Administration (ISCAE)', 'ar' => 'المعهد العالي للتجارة وإدارة المقاولات'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'National Schools of Commerce and Management (ENCG Network)', 'ar' => 'شبكة المدارس الوطنية للتجارة والتسيير'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Hassan II Agronomic and Veterinary Institute (IAV)', 'ar' => 'معهد الحسن الثاني للزراعة والبيطرة'],
                'country_id' => 149,
            ],

            //== Private & International Partnership Universities ==
            [
                'name' => ['en' => 'Al Akhawayn University in Ifrane (AUI)', 'ar' => 'جامعة الأخوين بإفران'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'International University of Rabat (UIR)', 'ar' => 'الجامعة الدولية للرباط'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Mohammed VI Polytechnic University (UM6P)', 'ar' => 'جامعة محمد السادس متعددة التخصصات التقنية'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Euro-Mediterranean University of Fes (UEMF)', 'ar' => 'الجامعة الأورومتوسطية بفاس'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Mundiapolis University', 'ar' => 'جامعة مونديابوليس'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'Private University of Marrakech (UPM)', 'ar' => 'الجامعة الخاصة بمراكش'],
                'country_id' => 149,
            ],
            [
                'name' => ['en' => 'International University of Casablanca (UIC)', 'ar' => 'الجامعة الدولية بالدار البيضاء'],
                'country_id' => 149,
            ],
        ];

        foreach ($universities as $university) {
            University::create([
                    'name' => [
                        'en' => $university['name']['en'],
                        'ar' => $university['name']['ar'],
                    ],
                    'country_id'=>$university['country_id']
                ]);
        }
    }
}
