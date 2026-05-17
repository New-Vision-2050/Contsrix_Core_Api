<?php

namespace Modules\Shared\AcademicSpecialization\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;
use Ranium\SeedOnce\Traits\SeedOnce;

class AcademicSpecializationssSeederTableSeeder extends Seeder
{
//    use SeedOnce;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $specializations = [
            // --- Engineering ---
            ['ar' => 'هندسة مدنية', 'en' => 'Civil Engineering', 'code' => 'civil_engineering'],
            ['ar' => 'هندسة كهربائية', 'en' => 'Electrical Engineering', 'code' => 'electrical_engineering'],
            ['ar' => 'هندسة ميكانيكية', 'en' => 'Mechanical Engineering', 'code' => 'mechanical_engineering'],
            ['ar' => 'هندسة معمارية', 'en' => 'Architectural Engineering', 'code' => 'architectural_engineering'],
            ['ar' => 'هندسة كيميائية', 'en' => 'Chemical Engineering', 'code' => 'chemical_engineering'],
            ['ar' => 'هندسة بترول', 'en' => 'Petroleum Engineering', 'code' => 'petroleum_engineering'],
            ['ar' => 'هندسة حاسوب', 'en' => 'Computer Engineering', 'code' => 'computer_engineering'],
            ['ar' => 'هندسة اتصالات وإلكترونيات', 'en' => 'Communications and Electronics Engineering', 'code' => 'communications_electronics_engineering'],
            ['ar' => 'هندسة طيران وفضاء', 'en' => 'Aerospace Engineering', 'code' => 'aerospace_engineering'],
            ['ar' => 'هندسة صناعية', 'en' => 'Industrial Engineering', 'code' => 'industrial_engineering'],
            ['ar' => 'هندسة الميكاترونكس', 'en' => 'Mechatronics Engineering', 'code' => 'mechatronics_engineering'],
            ['ar' => 'هندسة طبية حيوية', 'en' => 'Biomedical Engineering', 'code' => 'biomedical_engineering'],
            ['ar' => 'هندسة بيئية', 'en' => 'Environmental Engineering', 'code' => 'environmental_engineering'],
            ['ar' => 'هندسة بحرية وعمارة سفن', 'en' => 'Marine Engineering and Naval Architecture', 'code' => 'marine_engineering'],
            ['ar' => 'هندسة تعدين', 'en' => 'Mining Engineering', 'code' => 'mining_engineering'],
            ['ar' => 'هندسة نووية', 'en' => 'Nuclear Engineering', 'code' => 'nuclear_engineering'],
            ['ar' => 'هندسة إنشائية', 'en' => 'Structural Engineering', 'code' => 'structural_engineering'],
            ['ar' => 'هندسة القوى الميكانيكية', 'en' => 'Mechanical Power Engineering', 'code' => 'mechanical_power_engineering'],
            ['ar' => 'هندسة القوى والآلات الكهربية', 'en' => 'Electrical Power and Machines Engineering', 'code' => 'electrical_power_machines_engineering'],

            // --- Computer Science & IT ---
            ['ar' => 'علوم الحاسوب', 'en' => 'Computer Science', 'code' => 'computer_science'],
            ['ar' => 'تكنولوجيا المعلومات', 'en' => 'Information Technology (IT)', 'code' => 'information_technology'],
            ['ar' => 'نظم المعلومات', 'en' => 'Information Systems (IS)', 'code' => 'information_systems'],
            ['ar' => 'هندسة البرمجيات', 'en' => 'Software Engineering', 'code' => 'software_engineering'],
            ['ar' => 'ذكاء اصطناعي', 'en' => 'Artificial Intelligence (AI)', 'code' => 'artificial_intelligence'],
            ['ar' => 'علم البيانات', 'en' => 'Data Science', 'code' => 'data_science'],
            ['ar' => 'الأمن السيبراني', 'en' => 'Cybersecurity', 'code' => 'cybersecurity'],
            ['ar' => 'شبكات الحاسوب', 'en' => 'Computer Networks', 'code' => 'computer_networks'],

            // --- Sciences ---
            ['ar' => 'فيزياء', 'en' => 'Physics', 'code' => 'physics'],
            ['ar' => 'كيمياء', 'en' => 'Chemistry', 'code' => 'chemistry'],
            ['ar' => 'أحياء (بيولوجيا)', 'en' => 'Biology', 'code' => 'biology'],
            ['ar' => 'رياضيات', 'en' => 'Mathematics', 'code' => 'mathematics'],
            ['ar' => 'جيولوجيا', 'en' => 'Geology', 'code' => 'geology'],
            ['ar' => 'علوم فلك', 'en' => 'Astronomy', 'code' => 'astronomy'],
            ['ar' => 'إحصاء', 'en' => 'Statistics', 'code' => 'statistics'],
            ['ar' => 'كيمياء حيوية', 'en' => 'Biochemistry', 'code' => 'biochemistry'],
            ['ar' => 'علوم بيئة', 'en' => 'Environmental Science', 'code' => 'environmental_science'],
            ['ar' => 'تكنولوجيا حيوية (بيوتكنولوجي)', 'en' => 'Biotechnology', 'code' => 'biotechnology'],
            ['ar' => 'جيوفيزياء', 'en' => 'Geophysics', 'code' => 'geophysics'],
            ['ar' => 'علوم بحار', 'en' => 'Marine Science / Oceanography', 'code' => 'marine_science'],
            ['ar' => 'علوم أكتوارية', 'en' => 'Actuarial Science', 'code' => 'actuarial_science'],

            // --- Medicine & Health Sciences ---
            ['ar' => 'طب وجراحة (طب بشري)', 'en' => 'Medicine and Surgery (MBBS / MBBCh)', 'code' => 'medicine'],
            ['ar' => 'طب أسنان', 'en' => 'Dentistry (BDS)', 'code' => 'dentistry'],
            ['ar' => 'صيدلة', 'en' => 'Pharmacy (B.Pharm / Pharm.D)', 'code' => 'pharmacy'],
            ['ar' => 'تمريض', 'en' => 'Nursing', 'code' => 'nursing'],
            ['ar' => 'علاج طبيعي', 'en' => 'Physical Therapy (Physiotherapy)', 'code' => 'physical_therapy'],
            ['ar' => 'علوم المختبرات الطبية', 'en' => 'Medical Laboratory Sciences', 'code' => 'medical_laboratory'],
            ['ar' => 'علوم الأشعة والتصوير الطبي', 'en' => 'Radiology and Medical Imaging Sciences', 'code' => 'radiology_imaging'],
            ['ar' => 'صحة عامة', 'en' => 'Public Health', 'code' => 'public_health'],
            ['ar' => 'تغذية وعلوم أطعمة (تغذية علاجية)', 'en' => 'Nutrition and Food Science (Clinical Nutrition)', 'code' => 'clinical_nutrition'],
            ['ar' => 'طب بيطري', 'en' => 'Veterinary Medicine', 'code' => 'veterinary_medicine'],
            ['ar' => 'علاج وظيفي', 'en' => 'Occupational Therapy', 'code' => 'occupational_therapy'],
            ['ar' => 'تقنيات التخدير', 'en' => 'Anesthesia Technology', 'code' => 'anesthesia_technology'],
            ['ar' => 'علاج تنفسي', 'en' => 'Respiratory Therapy', 'code' => 'respiratory_therapy'],
            ['ar' => 'بصريات وعلوم الرؤية', 'en' => 'Optometry and Vision Science', 'code' => 'optometry'],
            ['ar' => 'اضطرابات النطق واللغة (تخاطب)', 'en' => 'Speech-Language Pathology', 'code' => 'speech_language_pathology'],

            // --- Business, Administration & Finance ---
            ['ar' => 'إدارة أعمال', 'en' => 'Business Administration', 'code' => 'business_administration'],
            ['ar' => 'محاسبة', 'en' => 'Accounting', 'code' => 'accounting'],
            ['ar' => 'تمويل واستثمار', 'en' => 'Finance and Investment', 'code' => 'finance_investment'],
            ['ar' => 'تسويق', 'en' => 'Marketing', 'code' => 'marketing'],
            ['ar' => 'اقتصاد', 'en' => 'Economics', 'code' => 'economics'],
            ['ar' => 'إدارة الموارد البشرية', 'en' => 'Human Resource Management', 'code' => 'human_resource_management'],
            ['ar' => 'إدارة لوجستية وسلاسل الإمداد', 'en' => 'Logistics and Supply Chain Management', 'code' => 'logistics_supply_chain'],
            ['ar' => 'إدارة نظم المعلومات', 'en' => 'Management Information Systems (MIS)', 'code' => 'management_information_systems'],
            ['ar' => 'إدارة دولية', 'en' => 'International Business', 'code' => 'international_business'],
            ['ar' => 'ريادة أعمال', 'en' => 'Entrepreneurship', 'code' => 'entrepreneurship'],
            ['ar' => 'علوم مصرفية ومالية', 'en' => 'Banking and Financial Sciences', 'code' => 'banking_financial_sciences'],
            ['ar' => 'تجارة خارجية', 'en' => 'Foreign Trade', 'code' => 'foreign_trade'],
            ['ar' => 'إدارة عامة', 'en' => 'Public Administration', 'code' => 'public_administration'],

            // --- Arts, Humanities & Social Sciences ---
            ['ar' => 'لغة عربية وآدابها', 'en' => 'Arabic Language and Literature', 'code' => 'arabic_language_literature'],
            ['ar' => 'لغة إنجليزية وآدابها', 'en' => 'English Language and Literature', 'code' => 'english_language_literature'],
            ['ar' => 'لغة فرنسية وآدابها', 'en' => 'French Language and Literature', 'code' => 'french_language_literature'],
            ['ar' => 'لغات أخرى (ألمانية، إسبانية، إيطالية، صينية..)', 'en' => 'Other Languages (German, Spanish, Italian, Chinese..)', 'code' => 'other_languages'],
            ['ar' => 'ترجمة', 'en' => 'Translation', 'code' => 'translation'],
            ['ar' => 'تاريخ', 'en' => 'History', 'code' => 'history'],
            ['ar' => 'جغرافيا ونظم معلومات جغرافية', 'en' => 'Geography and GIS', 'code' => 'geography_gis'],
            ['ar' => 'فلسفة', 'en' => 'Philosophy', 'code' => 'philosophy'],
            ['ar' => 'علم اجتماع', 'en' => 'Sociology', 'code' => 'sociology'],
            ['ar' => 'علم نفس', 'en' => 'Psychology', 'code' => 'psychology'],
            ['ar' => 'علوم سياسية', 'en' => 'Political Science', 'code' => 'political_science'],
            ['ar' => 'خدمة اجتماعية', 'en' => 'Social Work', 'code' => 'social_work'],
            ['ar' => 'آثار', 'en' => 'Archaeology', 'code' => 'archaeology'],
            ['ar' => 'علم مصريات', 'en' => 'Egyptology', 'code' => 'egyptology'],
            ['ar' => 'علم مكتبات ومعلومات', 'en' => 'Library and Information Science', 'code' => 'library_information_science'],
            ['ar' => 'أنثروبولوجيا', 'en' => 'Anthropology', 'code' => 'anthropology'],

            // --- Law ---
            ['ar' => 'حقوق (قانون)', 'en' => 'Law', 'code' => 'law'],
            ['ar' => 'شريعة وقانون', 'en' => 'Sharia and Law', 'code' => 'sharia_and_law'],

            // --- Education ---
            ['ar' => 'تربية (تخصصات مختلفة)', 'en' => 'Education (Various Specializations)', 'code' => 'education_general'],
            ['ar' => 'تربية خاصة', 'en' => 'Special Education', 'code' => 'special_education'],
            ['ar' => 'رياض أطفال', 'en' => 'Kindergarten / Early Childhood Education', 'code' => 'kindergarten_education'],
            ['ar' => 'تكنولوجيا تعليم', 'en' => 'Educational Technology', 'code' => 'educational_technology'],
            ['ar' => 'مناهج وطرق تدريس', 'en' => 'Curriculum and Instruction', 'code' => 'curriculum_instruction'],
            ['ar' => 'علم النفس التربوي', 'en' => 'Educational Psychology', 'code' => 'educational_psychology'],

            // --- Agriculture ---
            ['ar' => 'علوم زراعية (عام)', 'en' => 'Agricultural Sciences (General)', 'code' => 'agricultural_sciences'],
            ['ar' => 'هندسة زراعية', 'en' => 'Agricultural Engineering', 'code' => 'agricultural_engineering'],
            ['ar' => 'إنتاج نباتي ووقاية نبات', 'en' => 'Plant Production and Protection', 'code' => 'plant_production'],
            ['ar' => 'إنتاج حيواني وداجني', 'en' => 'Animal and Poultry Production', 'code' => 'animal_poultry_production'],
            ['ar' => 'علوم وتكنولوجيا الأغذية', 'en' => 'Food Science and Technology', 'code' => 'food_science_technology'],
            ['ar' => 'اقتصاد زراعي', 'en' => 'Agricultural Economics', 'code' => 'agricultural_economics'],
            ['ar' => 'علوم التربة والمياه', 'en' => 'Soil and Water Sciences', 'code' => 'soil_water_sciences'],
            ['ar' => 'بساتين (هورتيكالتشر)', 'en' => 'Horticulture', 'code' => 'horticulture'],

            // --- Fine & Applied Arts ---
            ['ar' => 'فنون جميلة (تصوير، نحت، جرافيك)', 'en' => 'Fine Arts (Painting, Sculpture, Graphics)', 'code' => 'fine_arts'],
            ['ar' => 'فنون تطبيقية', 'en' => 'Applied Arts', 'code' => 'applied_arts'],
            ['ar' => 'تصميم جرافيك', 'en' => 'Graphic Design', 'code' => 'graphic_design'],
            ['ar' => 'تصميم داخلي (ديكور)', 'en' => 'Interior Design (Decor)', 'code' => 'interior_design'],
            ['ar' => 'تصميم أزياء ونسيج', 'en' => 'Fashion and Textile Design', 'code' => 'fashion_textile_design'],
            ['ar' => 'خزف', 'en' => 'Ceramics', 'code' => 'ceramics'],
            ['ar' => 'تصوير فوتوغرافي وسينمائي', 'en' => 'Photography and Cinematography', 'code' => 'photography_cinematography'],
            ['ar' => 'فنون مسرحية', 'en' => 'Theatrical Arts / Drama', 'code' => 'theatrical_arts'],
            ['ar' => 'تربية فنية', 'en' => 'Art Education', 'code' => 'art_education'],

            // --- Media & Communication ---
            ['ar' => 'إعلام (صحافة وإذاعة وتلفزيون)', 'en' => 'Media (Journalism, Radio & TV)', 'code' => 'media_journalism'],
            ['ar' => 'علاقات عامة وإعلان', 'en' => 'Public Relations and Advertising', 'code' => 'public_relations_advertising'],
            ['ar' => 'إعلام رقمي', 'en' => 'Digital Media', 'code' => 'digital_media'],

            // --- Tourism & Hospitality ---
            ['ar' => 'إدارة سياحة وفنادق', 'en' => 'Tourism and Hotel Management', 'code' => 'tourism_hotel_management'],
            ['ar' => 'إرشاد سياحي', 'en' => 'Tourist Guidance', 'code' => 'tourist_guidance'],
            ['ar' => 'دراسات سياحية', 'en' => 'Tourism Studies', 'code' => 'tourism_studies'],
            ['ar' => 'إدارة ضيافة', 'en' => 'Hospitality Management', 'code' => 'hospitality_management'],

            // --- Physical Education ---
            ['ar' => 'تربية بدنية وعلوم رياضة', 'en' => 'Physical Education and Sports Science', 'code' => 'physical_education'],

            // --- Islamic Studies ---
            ['ar' => 'شريعة إسلامية', 'en' => 'Islamic Sharia', 'code' => 'islamic_sharia'],
            ['ar' => 'أصول الدين', 'en' => 'Fundamentals of Religion (Usul al-Din)', 'code' => 'usul_al_din'],
            ['ar' => 'دراسات إسلامية', 'en' => 'Islamic Studies', 'code' => 'islamic_studies'],
            ['ar' => 'دعوة وثقافة إسلامية', 'en' => 'Dawah and Islamic Culture', 'code' => 'dawah_islamic_culture'],
            ['ar' => 'قراءات', 'en' => 'Quranic Readings (Qira\'at)', 'code' => 'quranic_readings'],

            // --- Safety & Security ---
            ['ar' => 'هندسة الأمن والسلامة', 'en' => 'Safety and Security Engineering', 'code' => 'safety_security_engineering'],

        ];

        foreach ($specializations as $index => $item) {
            $exists = AcademicSpecialization::where('code', $item['code'])
                ->orWhereHas('translations', function ($query) use ($item) {
                    $query->where('content', $item['ar'])
                        ->orWhere('content', $item['en']);
                })
                ->first();

            if (!$exists) {
                AcademicSpecialization::create([
                    'name' => ['en' => $item['en'], 'ar' => $item['ar']],
                    'code' => $item['code'],
                ]);
            }
        }
    }
}
