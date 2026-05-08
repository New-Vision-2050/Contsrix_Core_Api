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
            ['ar' => 'هندسة مدنية', 'en' => 'Civil Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة كهربائية', 'en' => 'Electrical Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة ميكانيكية', 'en' => 'Mechanical Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة معمارية', 'en' => 'Architectural Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة كيميائية', 'en' => 'Chemical Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة بترول', 'en' => 'Petroleum Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة حاسوب', 'en' => 'Computer Engineering', 'code' => 'engineering'], // Often in Engineering faculty
            ['ar' => 'هندسة اتصالات وإلكترونيات', 'en' => 'Communications and Electronics Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة طيران وفضاء', 'en' => 'Aerospace Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة صناعية', 'en' => 'Industrial Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة الميكاترونكس', 'en' => 'Mechatronics Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة طبية حيوية', 'en' => 'Biomedical Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة بيئية', 'en' => 'Environmental Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة بحرية وعمارة سفن', 'en' => 'Marine Engineering and Naval Architecture', 'code' => 'engineering'],
            ['ar' => 'هندسة تعدين', 'en' => 'Mining Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة نووية', 'en' => 'Nuclear Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة إنشائية', 'en' => 'Structural Engineering', 'code' => 'engineering'], // Sub-discipline of Civil
            ['ar' => 'هندسة القوى الميكانيكية', 'en' => 'Mechanical Power Engineering', 'code' => 'engineering'], // Sub-discipline of Mechanical
            ['ar' => 'هندسة القوى والآلات الكهربية', 'en' => 'Electrical Power and Machines Engineering', 'code' => 'engineering'], // Sub-discipline of Electrical

            // --- Computer Science & IT ---
            ['ar' => 'علوم الحاسوب', 'en' => 'Computer Science', 'code' => 'computer_science'],
            ['ar' => 'تكنولوجيا المعلومات', 'en' => 'Information Technology (IT)', 'code' => 'it'],
            ['ar' => 'نظم المعلومات', 'en' => 'Information Systems (IS)', 'code' => 'it'],
            ['ar' => 'هندسة البرمجيات', 'en' => 'Software Engineering', 'code' => 'computer_science'],
            ['ar' => 'ذكاء اصطناعي', 'en' => 'Artificial Intelligence (AI)', 'code' => 'computer_science'],
            ['ar' => 'علم البيانات', 'en' => 'Data Science', 'code' => 'computer_science'],
            ['ar' => 'الأمن السيبراني', 'en' => 'Cybersecurity', 'code' => 'computer_science'],
            ['ar' => 'شبكات الحاسوب', 'en' => 'Computer Networks', 'code' => 'computer_science'],

            // --- Sciences ---
            ['ar' => 'فيزياء', 'en' => 'Physics', 'code' => 'science'],
            ['ar' => 'كيمياء', 'en' => 'Chemistry', 'code' => 'science'],
            ['ar' => 'أحياء (بيولوجيا)', 'en' => 'Biology', 'code' => 'science'],
            ['ar' => 'رياضيات', 'en' => 'Mathematics', 'code' => 'science'],
            ['ar' => 'جيولوجيا', 'en' => 'Geology', 'code' => 'science'],
            ['ar' => 'علوم فلك', 'en' => 'Astronomy', 'code' => 'science'],
            ['ar' => 'إحصاء', 'en' => 'Statistics', 'code' => 'science'],
            ['ar' => 'كيمياء حيوية', 'en' => 'Biochemistry', 'code' => 'science'],
            ['ar' => 'علوم بيئة', 'en' => 'Environmental Science', 'code' => 'science'],
            ['ar' => 'تكنولوجيا حيوية (بيوتكنولوجي)', 'en' => 'Biotechnology', 'code' => 'science'],
            ['ar' => 'جيوفيزياء', 'en' => 'Geophysics', 'code' => 'science'],
            ['ar' => 'علوم بحار', 'en' => 'Marine Science / Oceanography', 'code' => 'science'],
            ['ar' => 'علوم أكتوارية', 'en' => 'Actuarial Science', 'code' => 'science'], // Also related to business/finance

            // --- Medicine & Health Sciences ---
            ['ar' => 'طب وجراحة (طب بشري)', 'en' => 'Medicine and Surgery (MBBS / MBBCh)', 'code' => 'medicine'],
            ['ar' => 'طب أسنان', 'en' => 'Dentistry (BDS)', 'code' => 'dentistry'],
            ['ar' => 'صيدلة', 'en' => 'Pharmacy (B.Pharm / Pharm.D)', 'code' => 'pharmacy'],
            ['ar' => 'تمريض', 'en' => 'Nursing', 'code' => 'nursing'],
            ['ar' => 'علاج طبيعي', 'en' => 'Physical Therapy (Physiotherapy)', 'code' => 'health_sciences'],
            ['ar' => 'علوم المختبرات الطبية', 'en' => 'Medical Laboratory Sciences', 'code' => 'health_sciences'],
            ['ar' => 'علوم الأشعة والتصوير الطبي', 'en' => 'Radiology and Medical Imaging Sciences', 'code' => 'health_sciences'],
            ['ar' => 'صحة عامة', 'en' => 'Public Health', 'code' => 'health_sciences'],
            ['ar' => 'تغذية وعلوم أطعمة (تغذية علاجية)', 'en' => 'Nutrition and Food Science (Clinical Nutrition)', 'code' => 'health_sciences'],
            ['ar' => 'طب بيطري', 'en' => 'Veterinary Medicine', 'code' => 'veterinary'],
            ['ar' => 'علاج وظيفي', 'en' => 'Occupational Therapy', 'code' => 'health_sciences'],
            ['ar' => 'تقنيات التخدير', 'en' => 'Anesthesia Technology', 'code' => 'health_sciences'],
            ['ar' => 'علاج تنفسي', 'en' => 'Respiratory Therapy', 'code' => 'health_sciences'],
            ['ar' => 'بصريات وعلوم الرؤية', 'en' => 'Optometry and Vision Science', 'code' => 'health_sciences'],
            ['ar' => 'اضطرابات النطق واللغة (تخاطب)', 'en' => 'Speech-Language Pathology', 'code' => 'health_sciences'],

            // --- Business, Administration & Finance ---
            ['ar' => 'إدارة أعمال', 'en' => 'Business Administration', 'code' => 'business'],
            ['ar' => 'محاسبة', 'en' => 'Accounting', 'code' => 'business'],
            ['ar' => 'تمويل واستثمار', 'en' => 'Finance and Investment', 'code' => 'business'],
            ['ar' => 'تسويق', 'en' => 'Marketing', 'code' => 'business'],
            ['ar' => 'اقتصاد', 'en' => 'Economics', 'code' => 'economics'],
            ['ar' => 'إدارة الموارد البشرية', 'en' => 'Human Resource Management', 'code' => 'business'],
            ['ar' => 'إدارة لوجستية وسلاسل الإمداد', 'en' => 'Logistics and Supply Chain Management', 'code' => 'business'],
            ['ar' => 'إدارة نظم المعلومات', 'en' => 'Management Information Systems (MIS)', 'code' => 'business'], // Crosses with IT
            ['ar' => 'إدارة دولية', 'en' => 'International Business', 'code' => 'business'],
            ['ar' => 'ريادة أعمال', 'en' => 'Entrepreneurship', 'code' => 'business'],
            ['ar' => 'علوم مصرفية ومالية', 'en' => 'Banking and Financial Sciences', 'code' => 'business'],
            ['ar' => 'تجارة خارجية', 'en' => 'Foreign Trade', 'code' => 'business'],
            ['ar' => 'إدارة عامة', 'en' => 'Public Administration', 'code' => 'business'],

            // --- Arts, Humanities & Social Sciences ---
            ['ar' => 'لغة عربية وآدابها', 'en' => 'Arabic Language and Literature', 'code' => 'arts'],
            ['ar' => 'لغة إنجليزية وآدابها', 'en' => 'English Language and Literature', 'code' => 'arts'],
            ['ar' => 'لغة فرنسية وآدابها', 'en' => 'French Language and Literature', 'code' => 'arts'],
            ['ar' => 'لغات أخرى (ألمانية، إسبانية، إيطالية، صينية..)', 'en' => 'Other Languages (German, Spanish, Italian, Chinese..)', 'code' => 'arts'],
            ['ar' => 'ترجمة', 'en' => 'Translation', 'code' => 'arts'],
            ['ar' => 'تاريخ', 'en' => 'History', 'code' => 'humanities'],
            ['ar' => 'جغرافيا ونظم معلومات جغرافية', 'en' => 'Geography and GIS', 'code' => 'humanities'], // Sometimes Science
            ['ar' => 'فلسفة', 'en' => 'Philosophy', 'code' => 'humanities'],
            ['ar' => 'علم اجتماع', 'en' => 'Sociology', 'code' => 'social_science'],
            ['ar' => 'علم نفس', 'en' => 'Psychology', 'code' => 'social_science'],
            ['ar' => 'علوم سياسية', 'en' => 'Political Science', 'code' => 'social_science'],
            ['ar' => 'خدمة اجتماعية', 'en' => 'Social Work', 'code' => 'social_science'],
            ['ar' => 'آثار', 'en' => 'Archaeology', 'code' => 'humanities'],
            ['ar' => 'علم مصريات', 'en' => 'Egyptology', 'code' => 'humanities'], // Specific
            ['ar' => 'علم مكتبات ومعلومات', 'en' => 'Library and Information Science', 'code' => 'humanities'],
            ['ar' => 'أنثروبولوجيا', 'en' => 'Anthropology', 'code' => 'social_science'],

            // --- Law ---
            ['ar' => 'حقوق (قانون)', 'en' => 'Law', 'code' => 'law'],
            ['ar' => 'شريعة وقانون', 'en' => 'Sharia and Law', 'code' => 'law'], // Common combination

            // --- Education ---
            ['ar' => 'تربية (تخصصات مختلفة)', 'en' => 'Education (Various Specializations)', 'code' => 'education'],
            ['ar' => 'تربية خاصة', 'en' => 'Special Education', 'code' => 'education'],
            ['ar' => 'رياض أطفال', 'en' => 'Kindergarten / Early Childhood Education', 'code' => 'education'],
            ['ar' => 'تكنولوجيا تعليم', 'en' => 'Educational Technology', 'code' => 'education'],
            ['ar' => 'مناهج وطرق تدريس', 'en' => 'Curriculum and Instruction', 'code' => 'education'],
            ['ar' => 'علم النفس التربوي', 'en' => 'Educational Psychology', 'code' => 'education'],

            // --- Agriculture ---
            ['ar' => 'علوم زراعية (عام)', 'en' => 'Agricultural Sciences (General)', 'code' => 'agriculture'],
            ['ar' => 'هندسة زراعية', 'en' => 'Agricultural Engineering', 'code' => 'agriculture'], // Can overlap with Engineering
            ['ar' => 'إنتاج نباتي ووقاية نبات', 'en' => 'Plant Production and Protection', 'code' => 'agriculture'],
            ['ar' => 'إنتاج حيواني وداجني', 'en' => 'Animal and Poultry Production', 'code' => 'agriculture'],
            ['ar' => 'علوم وتكنولوجيا الأغذية', 'en' => 'Food Science and Technology', 'code' => 'agriculture'],
            ['ar' => 'اقتصاد زراعي', 'en' => 'Agricultural Economics', 'code' => 'agriculture'],
            ['ar' => 'علوم التربة والمياه', 'en' => 'Soil and Water Sciences', 'code' => 'agriculture'],
            ['ar' => 'بساتين (هورتيكالتشر)', 'en' => 'Horticulture', 'code' => 'agriculture'],

            // --- Fine & Applied Arts ---
            ['ar' => 'فنون جميلة (تصوير، نحت، جرافيك)', 'en' => 'Fine Arts (Painting, Sculpture, Graphics)', 'code' => 'fine_arts'],
            ['ar' => 'فنون تطبيقية', 'en' => 'Applied Arts', 'code' => 'applied_arts'],
            ['ar' => 'تصميم جرافيك', 'en' => 'Graphic Design', 'code' => 'applied_arts'],
            ['ar' => 'تصميم داخلي (ديكور)', 'en' => 'Interior Design (Decor)', 'code' => 'applied_arts'],
            ['ar' => 'تصميم أزياء ونسيج', 'en' => 'Fashion and Textile Design', 'code' => 'applied_arts'],
            ['ar' => 'خزف', 'en' => 'Ceramics', 'code' => 'applied_arts'],
            ['ar' => 'تصوير فوتوغرافي وسينمائي', 'en' => 'Photography and Cinematography', 'code' => 'fine_arts'], // Can be Media too
            ['ar' => 'فنون مسرحية', 'en' => 'Theatrical Arts / Drama', 'code' => 'fine_arts'],
            ['ar' => 'تربية فنية', 'en' => 'Art Education', 'code' => 'education'], // Linked to Fine Arts

            // --- Media & Communication ---
            ['ar' => 'إعلام (صحافة وإذاعة وتلفزيون)', 'en' => 'Media (Journalism, Radio & TV)', 'code' => 'media'],
            ['ar' => 'علاقات عامة وإعلان', 'en' => 'Public Relations and Advertising', 'code' => 'media'],
            ['ar' => 'إعلام رقمي', 'en' => 'Digital Media', 'code' => 'media'],

            // --- Tourism & Hospitality ---
            ['ar' => 'إدارة سياحة وفنادق', 'en' => 'Tourism and Hotel Management', 'code' => 'tourism'],
            ['ar' => 'إرشاد سياحي', 'en' => 'Tourist Guidance', 'code' => 'tourism'],
            ['ar' => 'دراسات سياحية', 'en' => 'Tourism Studies', 'code' => 'tourism'],
            ['ar' => 'إدارة ضيافة', 'en' => 'Hospitality Management', 'code' => 'tourism'],

            // --- Physical Education ---
            ['ar' => 'تربية بدنية وعلوم رياضة', 'en' => 'Physical Education and Sports Science', 'code' => 'physical_education'],

            // --- Islamic Studies ---
            ['ar' => 'شريعة إسلامية', 'en' => 'Islamic Sharia', 'code' => 'islamic_studies'],
            ['ar' => 'أصول الدين', 'en' => 'Fundamentals of Religion (Usul al-Din)', 'code' => 'islamic_studies'],
            ['ar' => 'دراسات إسلامية', 'en' => 'Islamic Studies', 'code' => 'islamic_studies'],
            ['ar' => 'دعوة وثقافة إسلامية', 'en' => 'Dawah and Islamic Culture', 'code' => 'islamic_studies'],
            ['ar' => 'قراءات', 'en' => 'Quranic Readings (Qira\'at)', 'code' => 'islamic_studies'],

            // --- Safety & Security ---
            ['ar' => 'هندسة الأمن والسلامة', 'en' => 'Safety and Security Engineering', 'code' => 'engineering-safety'],

        ];

        foreach ($specializations as $index => $item) {
            AcademicSpecialization::firstOrCreate(
                ['code' => $item["code"]],
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']],'code'=>$item['code']]
            );
        }
    }
}
