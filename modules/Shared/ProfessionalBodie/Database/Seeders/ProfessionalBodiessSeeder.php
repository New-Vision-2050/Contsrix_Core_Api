<?php

namespace Modules\Shared\ProfessionalBodie\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Language\Models\Language;
use Modules\Shared\ProfessionalBodie\Models\ProfessionalBodie;
use Ranium\SeedOnce\Traits\SeedOnce;
use Illuminate\Support\Facades\DB;
class ProfessionalBodiessSeeder extends Seeder
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

        $professionalBodies = [
            ['ar' => 'نقابة المهندسين المصرية', 'en' => 'Egyptian Syndicate of Engineers', 'code' => 'engineering', 'country_id' => 65],
            ['ar' => 'الهيئة السعودية للمهندسين', 'en' => 'Saudi Council of Engineers', 'code' => 'engineering', 'country_id' => 194],

            ['ar' => 'نقابة الأطباء المصرية', 'en' => 'Egyptian Medical Syndicate', 'code' => 'medicine', 'country_id' => 65],
            ['ar' => 'الهيئة السعودية للتخصصات الصحية', 'en' => 'Saudi Commission for Health Specialties', 'code' => 'medicine', 'country_id' => 194],

            ['ar' => 'نقابة أطباء الأسنان المصرية', 'en' => 'Egyptian Dental Syndicate', 'code' => 'dentistry', 'country_id' => 65],
            ['ar' => 'الهيئة السعودية للتخصصات الصحية', 'en' => 'Saudi Commission for Health Specialties', 'code' => 'dentistry', 'country_id' => 194],

            ['ar' => 'نقابة الصيادلة المصرية', 'en' => 'Egyptian Pharmacists Syndicate', 'code' => 'pharmacy', 'country_id' => 65],
            ['ar' => 'الهيئة السعودية للتخصصات الصحية', 'en' => 'Saudi Commission for Health Specialties', 'code' => 'pharmacy', 'country_id' => 194],

            ['ar' => 'نقابة المحامين المصرية', 'en' => 'Egyptian Bar Association', 'code' => 'law', 'country_id' => 65],
            ['ar' => 'الهيئة السعودية للمحامين', 'en' => 'Saudi Bar Association', 'code' => 'law', 'country_id' => 194],

            ['ar' => 'نقابة التجاريين المصرية', 'en' => 'Egyptian Syndicate of Commercial Professions', 'code' => 'accounting', 'country_id' => 65],
            ['ar' => 'الهيئة السعودية للمراجعين والمحاسبين', 'en' => 'Saudi Organization for Chartered and Professional Accountants (SOCPA)', 'code' => 'accounting', 'country_id' => 194],

            ['ar' => 'نقابة التمريض المصرية', 'en' => 'Egyptian Nursing Syndicate', 'code' => 'nursing', 'country_id' => 65],
            ['ar' => 'الهيئة السعودية للتخصصات الصحية', 'en' => 'Saudi Commission for Health Specialties', 'code' => 'nursing', 'country_id' => 194],

            ['ar' => 'نقابة الصحفيين المصرية', 'en' => 'Egyptian Press Syndicate', 'code' => 'journalism', 'country_id' => 65],
            ['ar' => 'هيئة الصحفيين السعوديين', 'en' => 'Saudi Journalists Association', 'code' => 'journalism', 'country_id' => 194],

            ['ar' => 'نقابة المهن التعليمية المصرية', 'en' => 'Egyptian Syndicate of Teaching Professions', 'code' => 'teaching', 'country_id' => 65],

            ['ar' => 'النقابة العامة للعلاج الطبيعي المصرية', 'en' => 'General Syndicate for Physical Therapy - Egypt', 'code' => 'physical_therapy', 'country_id' => 65],
            ['ar' => 'الهيئة السعودية للتخصصات الصحية', 'en' => 'Saudi Commission for Health Specialties', 'code' => 'physical_therapy', 'country_id' => 194],

            ['ar' => 'النقابة العامة للأطباء البيطريين المصرية', 'en' => 'General Syndicate of Egyptian Veterinarians', 'code' => 'veterinary', 'country_id' => 65],

            ['ar' => 'نقابة المهن الزراعية المصرية', 'en' => 'Egyptian Syndicate of Agricultural Professions', 'code' => 'agriculture', 'country_id' => 65],

            ['ar' => 'نقابة المهن العلمية المصرية', 'en' => 'Egyptian Syndicate of Scientific Professions', 'code' => 'science', 'country_id' => 65],

            ['ar' => 'نقابة مصممي الفنون التطبيقية المصرية', 'en' => 'Egyptian Syndicate of Applied Arts Designers', 'code' => 'applied_arts', 'country_id' => 65],

            ['ar' => 'نقابة الفنانين التشكيليين المصرية', 'en' => 'Egyptian Syndicate of Plastic Artists', 'code' => 'fine_arts', 'country_id' => 65],

            ['ar' => 'نقابة المهن الاجتماعية المصرية', 'en' => 'Egyptian Syndicate of Social Professions', 'code' => 'social_work', 'country_id' => 65],
            ['ar' => 'الهيئة السعودية للتخصصات الصحية', 'en' => 'Saudi Commission for Health Specialties', 'code' => 'social_work', 'country_id' => 194],

        ];


        foreach ($professionalBodies as $index => $item) {
            ProfessionalBodie::firstOrCreate(
                ['id' => $index + 1],
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']],'code'=>$item['code'] , 'country_id' => $item['country_id']]
            );
        }

        $this->command->info('Professional Bodies seeder finished successfully!');
    }
}


