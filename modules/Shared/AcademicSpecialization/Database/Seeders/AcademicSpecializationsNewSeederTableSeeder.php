<?php

namespace Modules\Shared\AcademicSpecialization\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\AcademicQualification\Models\AcademicQualification;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;
use Ranium\SeedOnce\Traits\SeedOnce; // Ensure this package is installed and configured if used
use Illuminate\Support\Str;          // For Str::uuid() and Str::slug()

class AcademicSpecializationsNewSeederTableSeeder extends Seeder
{
    use SeedOnce; // This trait prevents the seeder from running multiple times

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();


        $masterId = AcademicQualification::whereHas('translations', function ($q) {
            $q->where('content', 'Master');
        })->value('id');

        $licentiateId = AcademicQualification::whereHas('translations', function ($q) {
            $q->where('content', 'Licentiate');
        })->value('id');

        $industrialDiplomaId = AcademicQualification::whereHas('translations', function ($q) {
            $q->where('content', 'Industrial Diploma');
        })->value('id');

        $commercialDiplomaId = AcademicQualification::whereHas('translations', function ($q) {
            $q->where('content', 'Commercial Diploma');
        })->value('id');

        $agriculturalDiplomaId = AcademicQualification::whereHas('translations', function ($q) {
            $q->where('content', 'Agricultural Diploma');
        })->value('id');

        $specializations = [
            // --- Master's Degree Specializations (Examples) ---
            ['academic_qualification_id' => $masterId,   'code' => 'cs_master','ar' => 'ماجستير علوم الحاسوب',         'en' => 'Master of Computer Science'],
            ['academic_qualification_id' => $masterId,   'code' => 'mba',      'ar' => 'ماجستير إدارة أعمال',          'en' => 'Master of Business Administration (MBA)'],
            ['academic_qualification_id' => $masterId,   'code' => 'eng_mng',  'ar' => 'ماجستير هندسة إدارية',         'en' => 'Master of Engineering Management'],

            // --- Licentiate Degree Specializations (Examples) ---
            ['academic_qualification_id' => $licentiateId, 'code' => 'law',    'ar' => 'حقوق (قانون)',                 'en' => 'Law'],
            ['academic_qualification_id' => $licentiateId, 'code' => 'arabic_lit','ar' => 'لغة عربية وآدابها',         'en' => 'Arabic Language and Literature'],

            // --- Industrial Diploma Specializations ---
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'prod_mech', 'ar' => 'ميكانيكا إنتاج (تشغيل ماكينات)', 'en' => 'Production Mechanics (Machine Operation)'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'mech_inst', 'ar' => 'تركيبات ميكانيكية',                 'en' => 'Mechanical Installations'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'weld_form', 'ar' => 'لحام وتشكيل معادن',              'en' => 'Welding and Metal Forming'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'refrig_ac', 'ar' => 'تبريد وتكييف الهواء',             'en' => 'Refrigeration and Air Conditioning'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'auto_mech', 'ar' => 'ميكانيكا سيارات (مركبات)',        'en' => 'Automotive Mechanics (Vehicles)'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'heavy_equip','ar' => 'معدات ثقيلة',                    'en' => 'Heavy Equipment Mechanics'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'prec_mach', 'ar' => 'صيانة وإصلاح الآلات الدقيقة',      'en' => 'Precision Machinery Maintenance and Repair'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'foundry',   'ar' => 'سباكة ونماذج',                   'en' => 'Foundry and Pattern Making'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'furn_metal','ar' => 'نجارة أثاث وإنشاءات معدنية',     'en' => 'Furniture Carpentry and Metal Constructions'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'elec_inst', 'ar' => 'تركيبات ومعدات كهربية',          'en' => 'Electrical Installations and Equipment'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'ind_elec_auto','ar' => 'إلكترونيات صناعية وتحكم آلي',  'en' => 'Industrial Electronics and Automation Control'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'comp_maint','ar' => 'صيانة حاسب وشبكات',             'en' => 'Computer Maintenance and Networking'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'motor_wind','ar' => 'لف محركات ومحولات',              'en' => 'Motor and Transformer Winding'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'med_equip', 'ar' => 'صيانة أجهزة طبية',               'en' => 'Medical Equipment Maintenance'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'print_tech','ar' => 'طباعة',                          'en' => 'Printing Technology'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'textiles',  'ar' => 'غزل ونسيج وملابس جاهزة',        'en' => 'Spinning, Weaving, and Garments'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'plumbing',  'ar' => 'أعمال صحية (سباكة)',             'en' => 'Plumbing and Sanitary Works'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'woodwork',  'ar' => 'صناعات خشبية (نجارة)',           'en' => 'Woodworking Industries (Carpentry)'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'arch_ind',  'ar' => 'صناعات معمارية (إنشاءات)',       'en' => 'Architectural Industries (Construction)'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'decor_adv', 'ar' => 'صناعات زخرفية وإعلان',          'en' => 'Decorative Industries and Advertising'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'leather_ind','ar' => 'صناعات جلدية',                  'en' => 'Leather Industries'],
            ['academic_qualification_id' => $industrialDiplomaId, 'code' => 'chem_lab_op','ar' => 'صناعات كيماوية (تشغيل معامل)', 'en' => 'Chemical Industries (Lab Operation)'],

            // --- Commercial Diploma Specializations (Examples) ---
            ['academic_qualification_id' => $commercialDiplomaId, 'code' => 'com_acc',  'ar' => 'محاسبة (دبلوم تجاري)',             'en' => 'Accounting (Commercial Diploma)'],
            ['academic_qualification_id' => $commercialDiplomaId, 'code' => 'com_sec',  'ar' => 'سكرتارية (دبلوم تجاري)',           'en' => 'Secretarial Studies (Commercial Diploma)'],
            ['academic_qualification_id' => $commercialDiplomaId, 'code' => 'com_mark', 'ar' => 'تسويق (دبلوم تجاري)',              'en' => 'Marketing (Commercial Diploma)'],

            // --- Agricultural Diploma Specializations (Examples) ---
            ['academic_qualification_id' => $agriculturalDiplomaId, 'code' => 'agr_plant_prod','ar' => 'إنتاج نباتي (دبلوم زراعي)', 'en' => 'Plant Production (Agricultural Diploma)'],
            ['academic_qualification_id' => $agriculturalDiplomaId, 'code' => 'agr_animal_prod','ar' => 'إنتاج حيواني (دبلوم زراعي)','en' => 'Animal Production (Agricultural Diploma)'],
            ['academic_qualification_id' => $agriculturalDiplomaId, 'code' => 'agr_food_ind','ar' => 'صناعات غذائية (دبلوم زراعي)','en' => 'Food Industries (Agricultural Diploma)'],
        ];


        foreach ($specializations as $index => $item) {
            AcademicSpecialization::firstOrCreate(
                ['id' => $index + 1],
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']],'code'=>$item['code'],'academic_qualification_id'=>$item['academic_qualification_id']]
            );
        }
    }
}
