<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\ProjectManagement\Models\Contractor;

class ContractorSeeder extends Seeder
{
    /**
     * Seed the predefined list of contractors for all companies.
     */
    public function run(): void
    {
        // Get all companies when no tenant is initialized, otherwise fall back to current tenant.
        $companyIds = Company::query()->pluck('id');

        if ($companyIds->isEmpty()) {
            $this->command->warn('No companies found. Contractors not seeded.');
            return;
        }

        $contractors = [
            'الشركة السعودية للتجارة والمقاولات ساتيك',
            'الشركة الصينية السعودي',
            'شركة اسكانكم للمقاولات',
            'شركة الاصايل للمقاولات',
            'شركة الجناحين للمقاولات',
            'شركة الجوان المطورة للمقاولات',
            'شركة العزائم لتقنية الكهرباء',
            'شركة المشروعات المتقدمة المحدودة',
            'شركة الميال للمقاولات المحدودة',
            'شركة النجاح المتكاملة للمقاولات',
            'شركة الهاجدية للمقاولات المحدودة',
            'شركة عبر المملكة للطاقة المحدودة',
            'شركة عوض بن ظفره للمقاولات',
            'شركة مجموعة مملكة التنمية',
            'شركة محمد عادل حالول للمقاولات',
            'شركة مستورة للمقاولات المحدودة',
            'شركه العجيمى للمقاولات',
            'شركه نسر الجزيره للمقاولات',
            'مؤسسة السهلي للتجارة',
            'شركة ايجاب السعودية للمقاولات',
            'شركة اعراب للمقاولات',
            'الشركة العالمية للصناعات الحديثة',
            'شركة التقنية والتشغيل للمقاول',
        ];

        DB::transaction(function () use ($companyIds, $contractors) {
            foreach ($companyIds as $companyId) {
                foreach ($contractors as $index => $name) {
                    $sequence = $index + 1;

                    Contractor::firstOrCreate(
                        [
                            'company_id' => $companyId,
                            'name' => $name,
                        ],
                        [
                            'number' => 'CNT-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
                            'is_active' => true,
                        ]
                    );
                }
            }
        });

        $this->command->info('Contractors seeded successfully: ' . count($contractors) . ' contractors x ' . $companyIds->count() . ' companies.');
    }
}
