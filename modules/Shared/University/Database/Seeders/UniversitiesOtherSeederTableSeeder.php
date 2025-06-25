<?php

namespace Modules\Shared\University\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\University\Models\University;
use Ranium\SeedOnce\Traits\SeedOnce;

class UniversitiesOtherSeederTableSeeder extends Seeder
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

        // --- TUNISIA (Country ID: 224) ---
        $tunisia_universities = [
            // == Main Public Universities ==
            ['ar' => 'جامعة تونس المنار', 'en' => 'Université de Tunis El Manar', 'country_id' => 224, 'link' => null],
            ['ar' => 'جامعة قرطاج', 'en' => 'Université de Carthage', 'country_id' => 224, 'link' => null],
            ['ar' => 'جامعة تونس', 'en' => 'Université de Tunis', 'country_id' => 224, 'link' => null],
            ['ar' => 'جامعة منوبة', 'en' => 'Université de la Manouba', 'country_id' => 224, 'link' => null],
            ['ar' => 'جامعة صفاقس', 'en' => 'Université de Sfax', 'country_id' => 224, 'link' => null],
            ['ar' => 'جامعة سوسة', 'en' => 'Université de Sousse', 'country_id' => 224, 'link' => null],
            ['ar' => 'جامعة المنستير', 'en' => 'Université de Monastir', 'country_id' => 224, 'link' => null],
            ['ar' => 'جامعة قابس', 'en' => 'Université de Gabès', 'country_id' => 224, 'link' => null],
            ['ar' => 'جامعة قفصة', 'en' => 'Université de Gafsa', 'country_id' => 224, 'link' => null],
            ['ar' => 'جامعة جندوبة', 'en' => 'Université de Jendouba', 'country_id' => 224, 'link' => null],
            ['ar' => 'جامعة القيروان', 'en' => 'Université de Kairouan', 'country_id' => 224, 'link' => null],
            ['ar' => 'جامعة الزيتونة', 'en' => 'Université Ezzitouna', 'country_id' => 224, 'link' => null],

            // == Prominent Engineering Schools (Écoles d'Ingénieurs) ==
            ['ar' => 'المدرسة الوطنية للمهندسين بتونس', 'en' => 'National Engineering School of Tunis (ENIT)', 'country_id' => 224, 'link' => null],
            ['ar' => 'المدرسة التونسية للتقنيات', 'en' => 'Polytechnic School of Tunisia (EPT)', 'country_id' => 224, 'link' => null],
            ['ar' => 'المدرسة الوطنية للمهندسين بصفاقس', 'en' => 'National Engineering School of Sfax (ENIS)', 'country_id' => 224, 'link' => null],
            ['ar' => 'المدرسة الوطنية للمهندسين بسوسة', 'en' => 'National Engineering School of Sousse (ENISo)', 'country_id' => 224, 'link' => null],
            ['ar' => 'المدرسة الوطنية للمهندسين بالمنستير', 'en' => 'National Engineering School of Monastir (ENIM)', 'country_id' => 224, 'link' => null],

            // == Prominent Medical & Pharmacy Schools (Facultés de Médecine et de Pharmacie) ==
            ['ar' => 'كلية الطب بتونس', 'en' => 'Faculty of Medicine of Tunis', 'country_id' => 224, 'link' => null],
            ['ar' => 'كلية الطب بصفاقس', 'en' => 'Faculty of Medicine of Sfax', 'country_id' => 224, 'link' => null],
            ['ar' => 'كلية الطب بسوسة', 'en' => 'Faculty of Medicine of Sousse', 'country_id' => 224, 'link' => null],
            ['ar' => 'كلية الطب بالمنستير', 'en' => 'Faculty of Medicine of Monastir', 'country_id' => 224, 'link' => null],
            ['ar' => 'كلية الصيدلة بالمنستير', 'en' => 'Faculty of Pharmacy of Monastir', 'country_id' => 224, 'link' => null],

            // == Prominent Business & Commerce Schools (Instituts et Écoles de Commerce) ==
            ['ar' => 'المعهد العالي للدراسات التجارية بقرطاج', 'en' => 'IHEC Carthage (Higher Institute of Commercial Studies)', 'country_id' => 224, 'link' => null],
            ['ar' => 'المعهد العالي للتصرف بتونس', 'en' => 'ISG Tunis (Higher Institute of Management of Tunis)', 'country_id' => 224, 'link' => null],
            ['ar' => 'المدرسة العليا للعلوم الاقتصادية والتجارية بتونس', 'en' => 'ESSEC Tunis (Higher School of Economic and Commercial Sciences)', 'country_id' => 224, 'link' => null],
            
            // == Technological Studies Institutes Network (ISETs) ==
            ['ar' => 'المعاهد العليا للدراسات التكنولوجية', 'en' => 'Higher Institutes of Technological Studies (ISET Network)', 'country_id' => 224, 'link' => null],
            
             // == Some well-known Private Universities ==
            ['ar' => 'الجامعة الخاصة بتونس', 'en' => 'Université Libre de Tunis (ULT)', 'country_id' => 224, 'link' => null],
            ['ar' => 'جامعة اسبري الخاصة', 'en' => 'ESPRIT (Private Higher School of Engineering and Technology)', 'country_id' => 224, 'link' => null],
            ['ar' => 'الجامعة المركزية', 'en' => 'Université Centrale', 'country_id' => 224, 'link' => null],
        ];

        // --- SYRIA (Country ID: 215) ---
        $syria_universities = [
            // == Public Universities (Government-controlled) ==
            ['ar' => 'جامعة دمشق', 'en' => 'University of Damascus', 'country_id' => 215, 'link' => null],
            ['ar' => 'جامعة حلب', 'en' => 'University of Aleppo', 'country_id' => 215, 'link' => null],
            ['ar' => 'جامعة تشرين', 'en' => 'Tishreen University', 'country_id' => 215, 'link' => null],
            ['ar' => 'جامعة البعث', 'en' => 'Al-Baath University', 'country_id' => 215, 'link' => null],
            ['ar' => 'جامعة الفرات', 'en' => 'Al-Furat University', 'country_id' => 215, 'link' => null],
            ['ar' => 'جامعة حماة', 'en' => 'Hama University', 'country_id' => 215, 'link' => null],
            ['ar' => 'جامعة طرطوس', 'en' => 'Tartous University', 'country_id' => 215, 'link' => null],
            ['ar' => 'الجامعة الافتراضية السورية', 'en' => 'Syrian Virtual University (SVU)', 'country_id' => 215, 'link' => null],
            ['ar' => 'المعهد العالي للعلوم التطبيقية والتكنولوجيا', 'en' => 'Higher Institute for Applied Sciences and Technology (HIAST)', 'country_id' => 215, 'link' => null],
            ['ar' => 'المعهد العالي لإدارة الأعمال', 'en' => 'Higher Institute of Business Administration (HIBA)', 'country_id' => 215, 'link' => null],

            // == Private Universities (Licensed by the Syrian Government) ==
            ['ar' => 'جامعة القلمون الخاصة', 'en' => 'University of Kalamoon (UOK)', 'country_id' => 215, 'link' => null],
            ['ar' => 'الجامعة الدولية الخاصة للعلوم والتكنولوجيا', 'en' => 'International University for Science and Technology (IUST)', 'country_id' => 215, 'link' => null],
            ['ar' => 'الجامعة العربية الدولية', 'en' => 'Arab International University (AIU)', 'country_id' => 215, 'link' => null],
            ['ar' => 'جامعة الوادي الدولية الخاصة', 'en' => 'Wadi International University (WIU)', 'country_id' => 215, 'link' => null],
            ['ar' => 'جامعة اليرموك الخاصة', 'en' => 'Yarmouk Private University', 'country_id' => 215, 'link' => null],
            ['ar' => 'جامعة الشهباء الخاصة', 'en' => 'Al-Shahba University', 'country_id' => 215, 'link' => null],
            ['ar' => 'الجامعة الوطنية الخاصة', 'en' => 'Al-Wataniya Private University', 'country_id' => 215, 'link' => null],
            ['ar' => 'جامعة قاسيون الخاصة', 'en' => 'Qasyoun Private University', 'country_id' => 215, 'link' => null],
            ['ar' => 'جامعة المنارة الخاصة', 'en' => 'Al-Manara University', 'country_id' => 215, 'link' => null],

            // == Universities in Non-Government-Controlled Areas ==
            // Note: Recognition of these universities is limited or non-existent by the Syrian government.
            ['ar' => 'جامعة إدلب', 'en' => 'Idlib University', 'country_id' => 215, 'link' => null],
            ['ar' => 'جامعة حلب في المناطق المحررة', 'en' => 'Free Aleppo University', 'country_id' => 215, 'link' => null],
            ['ar' => 'جامعة الشام العالمية', 'en' => 'Sham International University', 'country_id' => 215, 'link' => null],
        ];

        // --- SUDAN (Country ID: 209) ---
        $sudan_universities = [
            // == Public Universities (Khartoum - Pre-conflict) ==
            ['ar' => 'جامعة الخرطوم', 'en' => 'University of Khartoum', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة السودان للعلوم والتكنولوجيا', 'en' => 'Sudan University of Science and Technology (SUST)', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة النيلين', 'en' => 'Al-Neelain University', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة أم درمان الإسلامية', 'en' => 'Omdurman Islamic University', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة الزعيم الأزهري', 'en' => 'Al-Zaiem Al-Azhari University', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة بحري', 'en' => 'University of Bahri', 'country_id' => 209, 'link' => null],

            // == Public Universities (Other States) ==
            ['ar' => 'جامعة الجزيرة', 'en' => 'University of Gezira', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة كردفان', 'en' => 'University of Kordofan', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة البحر الأحمر', 'en' => 'Red Sea University', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة كسلا', 'en' => 'Kassala University', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة شندي', 'en' => 'Shendi University', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة وادي النيل', 'en' => 'Nile Valley University', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة سنار', 'en' => 'Sinnar University', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة نيالا', 'en' => 'Nyala University', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة الفاشر', 'en' => 'Al Fashir University', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة الدلنج', 'en' => 'Dalanj University', 'country_id' => 209, 'link' => null],

            // == Private & Community Universities ==
            ['ar' => 'جامعة إفريقيا العالمية', 'en' => 'International University of Africa', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة الأحفاد للبنات', 'en' => 'Ahfad University for Women', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة العلوم الطبية والتكنولوجيا (جامعة مأمون حميدة)', 'en' => 'University of Medical Sciences and Technology (UMST)', 'country_id' => 209, 'link' => null],
            ['ar' => 'الجامعة الوطنية - السودان', 'en' => 'National University - Sudan', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة المستقبل', 'en' => 'Future University', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة الرباط الوطني', 'en' => 'University of Ribat', 'country_id' => 209, 'link' => null],
            ['ar' => 'جامعة المشرق', 'en' => 'Mashreq University', 'country_id' => 209, 'link' => null],
            ['ar' => 'كلية كامبردج الدولية', 'en' => 'Cambridge International College', 'country_id' => 209, 'link' => null],
            ['ar' => 'الكلية الكندية السودانية', 'en' => 'Canadian Sudanese College', 'country_id' => 209, 'link' => null],

            // == Specialized Universities and Colleges ==
            ['ar' => 'جامعة القرآن الكريم والعلوم الإسلامية', 'en' => 'University of the Holy Quran and Islamic Sciences', 'country_id' => 209, 'link' => null],
            ['ar' => 'أكاديمية السودان للعلوم', 'en' => 'Sudan Academy of Sciences (SAS)', 'country_id' => 209, 'link' => null],
            ['ar' => 'كلية غاردن سيتي للعلوم والتقنية', 'en' => 'Garden City College for Science and Technology', 'country_id' => 209, 'link' => null],
            ['ar' => 'كلية السودان الجامعية للبنات', 'en' => 'Sudan University College for Women', 'country_id' => 209, 'link' => null],
        ];


        // --- PAKISTAN (Country ID: 167) ---
        $pakistan_universities = [
            // == Public General Universities ==
            ['ar' => 'جامعة البنجاب', 'en' => 'University of the Punjab', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة القائد الأعظم', 'en' => 'Quaid-i-Azam University (QAU)', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة كراتشي', 'en' => 'University of Karachi', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة بيشاور', 'en' => 'University of Peshawar', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة الكلية الحكومية، لاهور', 'en' => 'Government College University (GCU), Lahore', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة بهاء الدين زكريا', 'en' => 'Bahauddin Zakariya University (BZU)', 'country_id' => 167, 'link' => null],

            // == Specialized Public Universities ==
            // Engineering & Applied Sciences
            ['ar' => 'المعهد الباكستاني للهندسة والعلوم التطبيقية', 'en' => 'Pakistan Institute of Engineering and Applied Sciences (PIEAS)', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة الهندسة والتكنولوجيا، لاهور', 'en' => 'University of Engineering and Technology (UET), Lahore', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة NED للهندسة والتكنولوجيا', 'en' => 'NED University of Engineering and Technology, Karachi', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة تكنولوجيا المعلومات', 'en' => 'Information Technology University (ITU), Lahore', 'country_id' => 167, 'link' => null],
            // Medical & Health Sciences
            ['ar' => 'جامعة الملك إدوارد الطبية', 'en' => 'King Edward Medical University (KEMU)', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة داو للعلوم الصحية', 'en' => 'Dow University of Health Sciences (DUHS)', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة العلوم الصحية، لاهور', 'en' => 'University of Health Sciences (UHS), Lahore', 'country_id' => 167, 'link' => null],
            // Agriculture
            ['ar' => 'جامعة فيصل أباد للزراعة', 'en' => 'University of Agriculture, Faisalabad (UAF)', 'country_id' => 167, 'link' => null],
            // Business Administration
            ['ar' => 'معهد إدارة الأعمال، كراتشي', 'en' => 'Institute of Business Administration (IBA), Karachi', 'country_id' => 167, 'link' => null],

            // == Leading Private Universities ==
            ['ar' => 'جامعة لاهور للعلوم الإدارية', 'en' => 'Lahore University of Management Sciences (LUMS)', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة الآغا خان', 'en' => 'Aga Khan University (AKU)', 'country_id' => 167, 'link' => null],
            ['ar' => 'معهد غلام إسحاق خان للهندسة والعلوم والتكنولوجيا', 'en' => 'Ghulam Ishaq Khan Institute of Engineering Sciences and Technology (GIKI)', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة حبيب', 'en' => 'Habib University', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة كومساتس إسلام أباد', 'en' => 'COMSATS University Islamabad (CUI)', 'country_id' => 167, 'link' => null],
            ['ar' => 'معهد تكنولوجيا الفضاء', 'en' => 'Institute of Space Technology (IST), Islamabad', 'country_id' => 167, 'link' => null],

            // == Military-Administered Universities ==
            ['ar' => 'الجامعة الوطنية للعلوم والتكنولوجيا', 'en' => 'National University of Sciences and Technology (NUST)', 'country_id' => 167, 'link' => null],
            ['ar' => 'الجامعة الوطنية للعلوم الطبية', 'en' => 'National University of Medical Sciences (NUMS)', 'country_id' => 167, 'link' => null],
            ['ar' => 'الجامعة الجوية', 'en' => 'Air University, Islamabad', 'country_id' => 167, 'link' => null],
            ['ar' => 'جامعة بحرية', 'en' => 'Bahria University, Islamabad', 'country_id' => 167, 'link' => null],
        ];


        // SOMALIA (Country ID: 203) ---
        $somalia_universities = [
            // Government University
            ['ar' => 'الجامعة الوطنية الصومالية', 'en' => 'Somali National University (SNU)', 'country_id' => 203, 'link' => null],

            // Prominent Private Universities
            ['ar' => 'جامعة مقديشو', 'en' => 'Mogadishu University', 'country_id' => 203, 'link' => null],
            ['ar' => 'جامعة بنادر', 'en' => 'Benadir University', 'country_id' => 203, 'link' => null],
            ['ar' => 'جامعة سيماد', 'en' => 'SIMAD University', 'country_id' => 203, 'link' => null],
            ['ar' => 'جامعة جمهورية للعلوم والتكنولوجيا', 'en' => 'Jamhuriya University of Science and Technology (JUST)', 'country_id' => 203, 'link' => null],
            ['ar' => 'جامعة هورسيد الدولية', 'en' => 'HORSEED International University', 'country_id' => 203, 'link' => null],
            ['ar' => 'جامعة جوبكي', 'en' => 'Jobkey University', 'country_id' => 203, 'link' => null],
            ['ar' => 'جامعة البلازما', 'en' => 'Plasma University', 'country_id' => 203, 'link' => null],
            ['ar' => 'الجامعة الصومالية', 'en' => 'University of Somalia (UNISO)', 'country_id' => 203, 'link' => null],
            ['ar' => 'جامعة المدينة العالمية', 'en' => 'Madina University', 'country_id' => 203, 'link' => null],

            // == Puntland ==
            ['ar' => 'جامعة ولاية بونتلاند', 'en' => 'Puntland State University (PSU)', 'country_id' => 203, 'link' => null],
            ['ar' => 'جامعة شرق إفريقيا', 'en' => 'East Africa University (EAU)', 'country_id' => 203, 'link' => null],
            ['ar' => 'جامعة بوساسو', 'en' => 'Bosaso University', 'country_id' => 203, 'link' => null],

            // == Somaliland ==
            ['ar' => 'جامعة هرجيسا', 'en' => 'University of Hargeisa', 'country_id' => 203, 'link' => null],
            ['ar' => 'جامعة عامود', 'en' => 'Amoud University', 'country_id' => 203, 'link' => null],
            ['ar' => 'جامعة جوليص', 'en' => 'Gollis University', 'country_id' => 203, 'link' => null],
            ['ar' => 'جامعة بورعو', 'en' => 'University of Burao', 'country_id' => 203, 'link' => null],
            ['ar' => 'جامعة أدماس', 'en' => 'Admas University College', 'country_id' => 203, 'link' => null],
        ];


        $india_universities = [
            // --- Institutes of National Importance (INIs) ---
            // Indian Institutes of Technology (IITs)
            ['ar' => 'المعهد الهندي للتكنولوجيا بومباي', 'en' => 'Indian Institute of Technology Bombay (IITB)', 'country_id' => 101, 'link' => null],
            ['ar' => 'المعهد الهندي للتكنولوجيا دلهي', 'en' => 'Indian Institute of Technology Delhi (IITD)', 'country_id' => 101, 'link' => null],
            ['ar' => 'المعهد الهندي للتكنولوجيا مدراس', 'en' => 'Indian Institute of Technology Madras (IITM)', 'country_id' => 101, 'link' => null],
            ['ar' => 'المعهد الهندي للتكنولوجيا كانبور', 'en' => 'Indian Institute of Technology Kanpur (IITK)', 'country_id' => 101, 'link' => null],
            ['ar' => 'المعهد الهندي للتكنولوجيا خراجبور', 'en' => 'Indian Institute of Technology Kharagpur (IIT-KGP)', 'country_id' => 101, 'link' => null],

            // Indian Institutes of Management (IIMs)
            ['ar' => 'المعهد الهندي للإدارة أحمد أباد', 'en' => 'Indian Institute of Management Ahmedabad (IIMA)', 'country_id' => 101, 'link' => null],
            ['ar' => 'المعهد الهندي للإدارة بنغالور', 'en' => 'Indian Institute of Management Bangalore (IIMB)', 'country_id' => 101, 'link' => null],
            ['ar' => 'المعهد الهندي للإدارة كولكاتا', 'en' => 'Indian Institute of Management Calcutta (IIMC)', 'country_id' => 101, 'link' => null],

            // All India Institutes of Medical Sciences (AIIMS)
            ['ar' => 'معهد عموم الهند للعلوم الطبية، نيودلهي', 'en' => 'All India Institute of Medical Sciences, New Delhi (AIIMS)', 'country_id' => 101, 'link' => null],

            // National Institutes of Technology (NITs)
            ['ar' => 'المعهد الوطني للتكنولوجيا، تيروتشيرابالي', 'en' => 'National Institute of Technology, Tiruchirappalli (NIT Trichy)', 'country_id' => 101, 'link' => null],
            ['ar' => 'المعهد الوطني للتكنولوجيا، وارانجال', 'en' => 'National Institute of Technology, Warangal (NIT Warangal)', 'country_id' => 101, 'link' => null],
            ['ar' => 'المعهد الوطني للتكنولوجيا، سوراتكال', 'en' => 'National Institute of Technology, Surathkal (NITK)', 'country_id' => 101, 'link' => null],

            // Indian Institute of Science (IISc)
            ['ar' => 'المعهد الهندي للعلوم، بنغالور', 'en' => 'Indian Institute of Science (IISc Bangalore)', 'country_id' => 101, 'link' => null],

            // --- Prominent Central Universities ---
            ['ar' => 'جامعة دلهي', 'en' => 'University of Delhi (DU)', 'country_id' => 101, 'link' => null],
            ['ar' => 'جامعة جواهر لال نهرو', 'en' => 'Jawaharlal Nehru University (JNU)', 'country_id' => 101, 'link' => null],
            ['ar' => 'جامعة باناراس الهندوسية', 'en' => 'Banaras Hindu University (BHU)', 'country_id' => 101, 'link' => null],
            ['ar' => 'جامعة عليكرة الإسلامية', 'en' => 'Aligarh Muslim University (AMU)', 'country_id' => 101, 'link' => null],
            ['ar' => 'جامعة حيدر أباد', 'en' => 'University of Hyderabad', 'country_id' => 101, 'link' => null],

            // --- Prominent State Universities ---
            ['ar' => 'جامعة مومباي', 'en' => 'University of Mumbai', 'country_id' => 101, 'link' => null],
            ['ar' => 'جامعة كولكاتا', 'en' => 'University of Calcutta', 'country_id' => 101, 'link' => null],
            ['ar' => 'جامعة آنا', 'en' => 'Anna University', 'country_id' => 101, 'link' => null],
            ['ar' => 'جامعة جادافبور', 'en' => 'Jadavpur University', 'country_id' => 101, 'link' => null],
            
            // --- Top Private & Deemed Universities ---
            ['ar' => 'معهد بيرلا للتكنولوجيا والعلوم، بيلاني', 'en' => 'Birla Institute of Technology and Science, Pilani (BITS Pilani)', 'country_id' => 101, 'link' => null],
            ['ar' => 'معهد فيلور للتكنولوجيا', 'en' => 'Vellore Institute of Technology (VIT)', 'country_id' => 101, 'link' => null],
            ['ar' => 'أكاديمية مانيبال للتعليم العالي', 'en' => 'Manipal Academy of Higher Education (MAHE)', 'country_id' => 101, 'link' => null],
            ['ar' => 'جامعة أشوكا', 'en' => 'Ashoka University', 'country_id' => 101, 'link' => null],
            ['ar' => 'جامعة أو. بي. جيندال العالمية', 'en' => 'O.P. Jindal Global University', 'country_id' => 101, 'link' => null],
            ['ar' => 'جامعة أميتي', 'en' => 'Amity University', 'country_id' => 101, 'link' => null],
        ];

        // --- BANGLADESH (Country ID: 19) ---
        $bangladesh_universities = [
            // Public General Universities
            ['ar' => 'جامعة دكا', 'en' => 'University of Dhaka', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة راجشاهي', 'en' => 'University of Rajshahi', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة شيتاغونغ', 'en' => 'University of Chittagong', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة جاهانغيرناغار', 'en' => 'Jahangirnagar University', 'country_id' => 19, 'link' => null],
            ['ar' => 'الجامعة الإسلامية، بنغلاديش', 'en' => 'Islamic University, Bangladesh', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة خولنا', 'en' => 'Khulna University', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة جاغاناث', 'en' => 'Jagannath University', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة كوميلا', 'en' => 'Comilla University', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة باريسال', 'en' => 'Barisal University', 'country_id' => 19, 'link' => null],

            // Specialized Public Universities - Engineering & Technology
            ['ar' => 'جامعة بنغلاديش للهندسة والتكنولوجيا', 'en' => 'Bangladesh University of Engineering and Technology (BUET)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة شيتاغونغ للهندسة والتكنولوجيا', 'en' => 'Chittagong University of Engineering & Technology (CUET)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة راجشاهي للهندسة والتكنولوجيا', 'en' => 'Rajshahi University of Engineering & Technology (RUET)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة خولنا للهندسة والتكنولوجيا', 'en' => 'Khulna University of Engineering & Technology (KUET)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة دكا للهندسة والتكنولوجيا', 'en' => 'Dhaka University of Engineering & Technology (DUET)', 'country_id' => 19, 'link' => null],

            // Specialized Public Universities - Science & Technology
            ['ar' => 'جامعة شاه جلال للعلوم والتكنولوجيا', 'en' => 'Shahjalal University of Science and Technology (SUST)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة حاجي محمد دانش للعلوم والتكنولوجيا', 'en' => 'Hajee Mohammad Danesh Science and Technology University (HSTU)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة باتواخالي للعلوم والتكنولوجيا', 'en' => 'Patuakhali Science and Technology University (PSTU)', 'country_id' => 19, 'link' => null],

            // Specialized Public Universities - Agriculture
            ['ar' => 'جامعة بنغلاديش الزراعية', 'en' => 'Bangladesh Agricultural University (BAU)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة شير بنغلا الزراعية', 'en' => 'Sher-e-Bangla Agricultural University (SAU)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة بانغاباندو الشيخ مجيب الرحمن الزراعية', 'en' => 'Bangabandhu Sheikh Mujibur Rahman Agricultural University (BSMRAU)', 'country_id' => 19, 'link' => null],

            // Specialized Public Universities - Medical
            ['ar' => 'جامعة بانغاباندو الشيخ مجيب الطبية', 'en' => 'Bangabandhu Sheikh Mujib Medical University (BSMMU)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة شيتاغونغ الطبية', 'en' => 'Chittagong Medical University', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة راجشاهي الطبية', 'en' => 'Rajshahi Medical University', 'country_id' => 19, 'link' => null],

            // Other Specialized Public Universities
            ['ar' => 'جامعة بنغلاديش للمنسوجات', 'en' => 'Bangladesh University of Textiles (BUTEX)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة بانغاباندو الشيخ مجيب الرحمن البحرية', 'en' => 'Bangabandhu Sheikh Mujibur Rahman Maritime University (BSMRMU)', 'country_id' => 19, 'link' => null],

            // Private Universities
            ['ar' => 'جامعة نورث ساوث', 'en' => 'North South University (NSU)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة براك', 'en' => 'BRAC University', 'country_id' => 19, 'link' => null],
            ['ar' => 'الجامعة الأمريكية الدولية - بنغلاديش', 'en' => 'American International University-Bangladesh (AIUB)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة إندبندنت، بنغلاديش', 'en' => 'Independent University, Bangladesh (IUB)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة إيست ويست', 'en' => 'East West University (EWU)', 'country_id' => 19, 'link' => null],
            ['ar' => 'الجامعة المتحدة الدولية', 'en' => 'United International University (UIU)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة دافوديل الدولية', 'en' => 'Daffodil International University (DIU)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة أحسن الله للعلوم والتكنولوجيا', 'en' => 'Ahsanullah University of Science and Technology (AUST)', 'country_id' => 19, 'link' => null],
            ['ar' => 'جامعة الفنون الحرة بنغلاديش', 'en' => 'University of Liberal Arts Bangladesh (ULAB)', 'country_id' => 19, 'link' => null],
            ['ar' => 'الجامعة الخضراء في بنغلاديش', 'en' => 'Green University of Bangladesh', 'country_id' => 19, 'link' => null],

            // International Universities
            ['ar' => 'الجامعة الإسلامية للتكنولوجيا', 'en' => 'Islamic University of Technology (IUT)', 'country_id' => 19, 'link' => null],
            ['ar' => 'الجامعة الآسيوية للمرأة', 'en' => 'Asian University for Women (AUW)', 'country_id' => 19, 'link' => null],
        ];


        // Combine all universities
        $all_universities = array_merge(
            $tunisia_universities,
            $syria_universities,
            $sudan_universities,
            $pakistan_universities,
            $somalia_universities,
            $india_universities,
            $bangladesh_universities
        );

        foreach ($all_universities as $university) {
            University::create([
                'name' => ['en' => $university['en'], 'ar' => $university['ar']],
                'country_id' => $university['country_id'],
                // Assuming you might add a 'link' column later, but it's not in the create call
            ]);
        }
    }
}