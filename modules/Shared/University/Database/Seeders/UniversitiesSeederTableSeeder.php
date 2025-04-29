<?php

namespace Modules\Shared\University\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;
use Modules\Shared\University\Models\University;
use Ranium\SeedOnce\Traits\SeedOnce;
use Illuminate\Support\Facades\DB;
class UniversitiesSeederTableSeeder extends Seeder
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

        // --- EGYPT (Country ID: 65) ---
        $egypt_universities = [
            ['ar' => 'جامعة القاهرة', 'en' => 'Cairo University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة عين شمس', 'en' => 'Ain Shams University', 'country_id' => 65, 'link' => null],
            ['ar' => 'الجامعة الأمريكية بالقاهرة', 'en' => 'The American University in Cairo', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة الإسكندرية', 'en' => 'Alexandria University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة حلوان', 'en' => 'Helwan University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة المنصورة', 'en' => 'Mansoura University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة أسيوط', 'en' => 'Assiut University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة الزقازيق', 'en' => 'Zagazig University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة الأزهر', 'en' => 'Al-Azhar University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة طنطا', 'en' => 'Tanta University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة قناة السويس', 'en' => 'Suez Canal University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة بنها', 'en' => 'Benha University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة المنيا', 'en' => 'Minia University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة جنوب الوادي', 'en' => 'South Valley University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة بني سويف', 'en' => 'Beni-Suef University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة الفيوم', 'en' => 'Fayoum University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة سوهاج', 'en' => 'Sohag University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة كفر الشيخ', 'en' => 'Kafrelsheikh University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة بورسعيد', 'en' => 'Port Said University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة دمنهور', 'en' => 'Damanhour University', 'country_id' => 65, 'link' => null],
            ['ar' => 'الجامعة البريطانية في مصر', 'en' => 'The British University in Egypt (BUE)', 'country_id' => 65, 'link' => null],
            ['ar' => 'الجامعة الألمانية بالقاهرة', 'en' => 'German University in Cairo (GUC)', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة المستقبل بمصر', 'en' => 'Future University in Egypt (FUE)', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة مصر للعلوم والتكنولوجيا', 'en' => 'Misr University for Science and Technology (MUST)', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة 6 أكتوبر', 'en' => 'October 6 University (O6U)', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة النيل', 'en' => 'Nile University', 'country_id' => 65, 'link' => null],
            ['ar' => 'الجامعة المصرية اليابانية للعلوم والتكنولوجيا', 'en' => 'Egypt-Japan University of Science and Technology (E-JUST)', 'country_id' => 65, 'link' => null],
            ['ar' => 'مدينة زويل للعلوم والتكنولوجيا', 'en' => 'Zewail City of Science and Technology', 'country_id' => 65, 'link' => null], // Technically a research/education city, often listed
            ['ar' => 'جامعة أسوان', 'en' => 'Aswan University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة دمياط', 'en' => 'Damietta University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة السويس', 'en' => 'Suez University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة الملك سلمان الدولية', 'en' => 'King Salman International University', 'country_id' => 65, 'link' => null],
            ['ar' => 'جامعة الجلالة', 'en' => 'Galala University', 'country_id' => 65, 'link' => null],
        ];

        // --- SAUDI ARABIA (Country ID: 194) ---
        $saudi_universities = [
            ['ar' => 'جامعة الملك سعود', 'en' => 'King Saud University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة الملك عبد العزيز', 'en' => 'King Abdulaziz University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة الملك فهد للبترول والمعادن', 'en' => 'King Fahd University of Petroleum and Minerals (KFUPM)', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة أم القرى', 'en' => 'Umm Al-Qura University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة الإمام محمد بن سعود الإسلامية', 'en' => 'Imam Muhammad ibn Saud Islamic University', 'country_id' => 194, 'link' => null],
            ['ar' => 'الجامعة الإسلامية بالمدينة المنورة', 'en' => 'Islamic University of Madinah', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة الملك خالد', 'en' => 'King Khalid University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة الأميرة نورة بنت عبد الرحمن', 'en' => 'Princess Nourah bint Abdulrahman University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة الملك عبد الله للعلوم والتقنية', 'en' => 'King Abdullah University of Science and Technology (KAUST)', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة الفيصل', 'en' => 'Alfaisal University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة الأمير سلطان', 'en' => 'Prince Sultan University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة القصيم', 'en' => 'Qassim University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة طيبة', 'en' => 'Taibah University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة الطائف', 'en' => 'Taif University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة حائل', 'en' => 'University of Ha\'il', 'country_id' => 194, 'link' => null], // Note the apostrophe handling
            ['ar' => 'جامعة جازان', 'en' => 'Jazan University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة الجوف', 'en' => 'Al Jouf University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة تبوك', 'en' => 'University of Tabuk', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة نجران', 'en' => 'Najran University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة الحدود الشمالية', 'en' => 'Northern Border University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة الأمير سطام بن عبد العزيز', 'en' => 'Prince Sattam Bin Abdulaziz University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة المجمعة', 'en' => 'Majmaah University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة شقراء', 'en' => 'Shaqra University', 'country_id' => 194, 'link' => null],
            ['ar' => 'الجامعة السعودية الالكترونية', 'en' => 'Saudi Electronic University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة جدة', 'en' => 'University of Jeddah', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة بيشة', 'en' => 'University of Bisha', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة حفر الباطن', 'en' => 'University of Hafar Al Batin', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة دار العلوم', 'en' => 'Dar Al Uloom University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة اليمامة', 'en' => 'Al Yamamah University', 'country_id' => 194, 'link' => null],
            ['ar' => 'جامعة عفت الأهلية', 'en' => 'Effat University', 'country_id' => 194, 'link' => null],
            ['ar' => 'الجامعة العربية المفتوحة', 'en' => 'Arab Open University', 'country_id' => 194, 'link' => null], // Has branches including Saudi Arabia
        ];

        // Combine all universities
        $all_universities = array_merge($egypt_universities, $saudi_universities);

        foreach ($all_universities as $university) {
            University::create(
                ['name' => ['en' => $university['en'], 'ar' => $university['ar']], 'country_id' => $university['country_id']]
            );
        }
    }
}
