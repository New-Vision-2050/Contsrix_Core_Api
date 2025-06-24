<?php

namespace Modules\Company\CompanyField\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Company\BusinessType\Models\BusinessType;
use Modules\Company\CompanyField\Models\CompanyField;
use Ranium\SeedOnce\Traits\SeedOnce;
use Ramsey\Uuid\Uuid;

class BusinessTypeSeederTableSeeder extends Seeder
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
    
        $types = [
            [
                'name' => ['ar' => 'إداري', 'en' => 'Administrative'],
                'description' => [
                    'ar' => 'شركة تعمل في الإدارة وتنظيم الأعمال',
                    'en' => 'Company working in administration and business organization'
                ]
            ],
            [
                'name' => ['ar' => 'هندسي', 'en' => 'Engineering'],
                'description' => [
                    'ar' => 'شركة متخصصة في المجالات الهندسية',
                    'en' => 'Company specialized in engineering fields'
                ]
            ],
            [
                'name' => ['ar' => 'طبي', 'en' => 'Medical'],
                'description' => [
                    'ar' => 'شركة تعمل في المجال الطبي أو الصحي',
                    'en' => 'Company in the medical or health field'
                ]
            ],
            [
                'name' => ['ar' => 'تجاري', 'en' => 'Commercial'],
                'description' => [
                    'ar' => 'شركة مختصة بالتجارة والاستيراد والتصدير',
                    'en' => 'Company engaged in trade, import and export'
                ]
            ],
            [
                'name' => ['ar' => 'صناعي', 'en' => 'Industrial'],
                'description' => [
                    'ar' => 'شركة تعمل في مجال الصناعة والتصنيع',
                    'en' => 'Company in the field of industry and manufacturing'
                ]
            ],
            [
                'name' => ['ar' => 'زراعي', 'en' => 'Agricultural'],
                'description' => [
                    'ar' => 'شركة متخصصة في الزراعة والمنتجات الزراعية',
                    'en' => 'Company specialized in agriculture and agricultural products'
                ]
            ],
            [
                'name' => ['ar' => 'تقني', 'en' => 'Technological'],
                'description' => [
                    'ar' => 'شركة تقنية أو تعمل في البرمجيات والحلول الذكية',
                    'en' => 'Tech company or working in software and smart solutions'
                ]
            ],
            [
                'name' => ['ar' => 'خدمي', 'en' => 'Service'],
                'description' => [
                    'ar' => 'شركة تقدم خدمات مباشرة للأفراد أو المؤسسات',
                    'en' => 'Company offering direct services to individuals or organizations'
                ]
            ],
            [
                'name' => ['ar' => 'استشاري', 'en' => 'Consulting'],
                'description' => [
                    'ar' => 'شركة تقدم خدمات استشارية',
                    'en' => 'Company offering consulting services'
                ]
            ],
            [
                'name' => ['ar' => 'أمني', 'en' => 'Security'],
                'description' => [
                    'ar' => 'شركة متخصصة في الأمن والحماية والمراقبة',
                    'en' => 'Company specialized in security and surveillance'
                ]
            ],
        ];

        foreach ($types as $type) {
            BusinessType::create([
                    'description' => $type['description'],
                    'name' => $type['name']
                ]);
        }
        
    }
}
