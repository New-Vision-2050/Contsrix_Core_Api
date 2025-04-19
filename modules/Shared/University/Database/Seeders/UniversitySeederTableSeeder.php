<?php

namespace Modules\Shared\University\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;
use Modules\Shared\University\Models\University;
use Ranium\SeedOnce\Traits\SeedOnce;

class UniversitySeederTableSeeder extends Seeder
{
    use SeedOnce;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $universities = [
            ['ar' => 'أكاديمية العلوم الطبية والتكنولوجيا', 'en' => 'Academy of Medical Sciences and Technology', 'country_id' => 207, 'link' => 'http://www.amst-edu.com/'],
            ['ar' => 'جامعة الأحفاد للبنات', 'en' => 'Ahfad University for Women', 'country_id' => 207, 'link' => 'http://www.ahfad.org/'],
            ['ar' => 'جامعة الزعيم الأزهري', 'en' => 'Alzaiem Alazhari University', 'country_id' => 207, 'link' => 'http://www.alazhari.net/'],
            ['ar' => 'جامعة التكنولوجيا', 'en' => 'Technology University', 'country_id' => 207, 'link' => 'http://www.bayantech.edu/'],
            ['ar' => 'جامعة الجزيرة', 'en' => 'Gezira University', 'country_id' => 207, 'link' => 'http://www.gezirauniversity.net/'],
            ['ar' => 'الجامعة الدولية في إفريقيا', 'en' => 'International University of Africa', 'country_id' => 207, 'link' => 'http://www.iua.edu.sd/'],
            ['ar' => 'جامعة كردفان', 'en' => 'Kordofan University', 'country_id' => 207, 'link' => 'http://www.uni-kordofan-edu.com/'],
            ['ar' => 'الدراسات التقنية', 'en' => 'Technical Studies', 'country_id' => 207, 'link' => 'http://www.nc.edu.sd/'],
            ['ar' => 'جامعة الرباط الوطني', 'en' => 'National Ribat University', 'country_id' => 207, 'link' => 'http://www.ribat.edu.sd/'],
            ['ar' => 'جامعة النيلين', 'en' => 'Neelain University', 'country_id' => 207, 'link' => 'http://www.neelain.edu.sd/'],
            ['ar' => 'جامعة وادي النيل', 'en' => 'Nile Valley University', 'country_id' => 207, 'link' => 'http://www.nilevalley.edu.sd/'],
            ['ar' => 'جامعة أم درمان الإسلامية', 'en' => 'Omdurman Islamic University', 'country_id' => 207, 'link' => 'http://www.oiu.edu.sd/'],
            ['ar' => 'جامعة السودان للعلوم والتكنولوجيا', 'en' => 'Sudan University of Science and Technology', 'country_id' => 207, 'link' => 'http://www.sustech.edu/'],
            ['ar' => 'جامعة جوبا', 'en' => 'University of Juba', 'country_id' => 207, 'link' => 'http://www.juba.edu.sd/'],
            ['ar' => 'جامعة الخرطوم', 'en' => 'University of Khartoum', 'country_id' => 207, 'link' => 'http://www.uofk.edu/'],
            ['ar' => 'جامعة نيالا', 'en' => 'University of Nyala', 'country_id' => 207, 'link' => 'http://nyalau.edu.sd/'],
            ['ar' => 'جامعة شندي', 'en' => 'University of Shendi', 'country_id' => 207, 'link' => 'http://www.ush.sd/'],
            ['ar' => 'جامعة عين شمس', 'en' => 'Ain Shams University', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة الأزهر', 'en' => 'Al-Azhar University', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة الإسكندرية', 'en' => 'Alexandria University', 'country_id' => 64, 'link' => null],
            ['ar' => 'الجامعة العربية للعلوم والتكنولوجيا والنقل البحري', 'en' => 'Arab Academy for Science, Technology and Maritime Transport', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة أسيوط', 'en' => 'Assiut University', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة القاهرة', 'en' => 'Cairo University', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة حلوان', 'en' => 'Helwan University Cairo', 'country_id' => 64, 'link' => null],
            ['ar' => 'المعهد العالي للتكنولوجيا - بنها', 'en' => 'Higher Institute of Technology - Benha', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة المنصورة', 'en' => 'Mansoura University', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة المنيا', 'en' => 'Minia University', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة المنوفية', 'en' => 'Minufiya University', 'country_id' => 64, 'link' => null],
            ['ar' => 'الجامعة المصرية الدولية', 'en' => 'Misr International University', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة مصر للعلوم والتكنولوجيا', 'en' => 'Misr University for Science and Technology', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة العلوم والفنون الحديثة', 'en' => 'Modern Science and Arts University', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة أكتوبر 6', 'en' => 'October 6 University', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة جنوب الوادي', 'en' => 'South Valley University', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة قناة السويس', 'en' => 'Suez Canal University', 'country_id' => 64, 'link' => null],
            ['ar' => 'جامعة طنطا', 'en' => 'Tanta University', 'country_id' => 64, 'link' => null],
            ['ar' => 'الجامعة الأمريكية في القاهرة', 'en' => 'The American University in Cairo', 'country_id' => 64, 'link' => null],
        ];

        foreach ($universities as $university) {
            University::create(
                ['name' => ['en' => $university['en'], 'ar' => $university['ar']], 'country_id' => $university['country_id']]
            );
        }
    }
}
