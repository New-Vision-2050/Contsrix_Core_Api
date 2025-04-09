<?php

namespace Modules\Company\CompanyField\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Company\CompanyField\Models\CompanyField;
use Ranium\SeedOnce\Traits\SeedOnce;
use Ramsey\Uuid\Uuid;

class CompanyFieldSeederTableSeeder extends Seeder
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
        $companyFields = [
            [
                'name' => 'المجال الغذائي',
                'description' => 'المطاعم، المقاهي، المخابز، محلات الوجبات السريعة، شركات التموين'
            ],
            [
                'name' => 'المجال الصحي',
                'description' => 'المستشفيات، العيادات الطبية، الصيدليات، المختبرات الطبية، مراكز العلاج الطبيعي'
            ],
            [
                'name' => 'المجال التعليمي',
                'description' => 'المدارس، الجامعات، المعاهد، مراكز التدريب، دور الحضانة'
            ],
            [
                'name' => 'المجال الفندقي والسياحي',
                'description' => 'الفنادق، المنتجعات، شركات السياحة والسفر، المنتزهات الترفيهية، المتاحف'
            ],
            [
                'name' => 'المجال التجاري',
                'description' => 'محلات التجزئة، المولات، محلات الجملة، المتاجر الإلكترونية'
            ],
            [
                'name' => 'المجال الصناعي',
                'description' => 'المصانع، الورش الصناعية، منشآت الطاقة، شركات التصنيع الغذائي'
            ],
            [
                'name' => 'المجال الخدمي',
                'description' => 'شركات النقل والشحن، شركات النظافة، شركات الصيانة، شركات الاستشارات'
            ],
            [
                'name' => 'المجال المالي',
                'description' => 'البنوك، شركات التأمين، شركات الصرافة، شركات الاستثمار'
            ],
            [
                'name' => 'المجال الترفيهي والثقافي',
                'description' => 'دور السينما، المكتبات، المتاحف، المعارض الفنية، الملاعب الرياضية'
            ],
            [
                'name' => 'المجال العقاري',
                'description' => 'شركات التطوير العقاري، شركات إدارة العقارات، شركات التسويق العقاري'
            ],
            [
                'name' => 'المجال التكنولوجي',
                'description' => 'شركات البرمجيات، شركات التكنولوجيا المالية (FinTech)، شركات الاتصالات'
            ],
            [
                'name' => 'مجال التجارة والتجزئة',
                'description' => ''
            ],
            [
                'name' => 'مجال الصناعة والتصنيع',
                'description' => ''
            ],
            [
                'name' => 'مجال الإنشاءات والمقاولات',
                'description' => ''
            ],
            [
                'name' => 'مجال الطاقة والموارد الطبيعية',
                'description' => ''
            ],
            [
                'name' => 'مجال التكنولوجيا والاتصالات',
                'description' => ''
            ],
            [
                'name' => 'مجال الزراعة والإنتاج الزراعي',
                'description' => ''
            ],
            [
                'name' => 'مجال الخدمات المالية والاستثمارية',
                'description' => ''
            ],
            [
                'name' => 'مجال النقل والشحن',
                'description' => ''
            ],
            [
                'name' => 'مجال الرعاية الصحية',
                'description' => ''
            ],
            [
                'name' => 'مجال التعليم والتدريب',
                'description' => ''
            ],
            [
                'name' => 'مجال السياحة والضيافة',
                'description' => ''
            ],
            [
                'name' => 'مجال الإعلام والإعلانات',
                'description' => ''
            ],
            [
                'name' => 'مجال البيئة والاستدامة',
                'description' => ''
            ],
            [
                'name' => 'مجال الخدمات الاستشارية',
                'description' => ''
            ],
        ];

        $namespace = Uuid::NAMESPACE_DNS;
        foreach ($companyFields as $companyField) {
            $id = Uuid::uuid5($namespace, $companyField['name'])->toString();
            CompanyField::insertOrIgnore(array_merge(['id'=>$id], $companyField));
        }
    }
}
