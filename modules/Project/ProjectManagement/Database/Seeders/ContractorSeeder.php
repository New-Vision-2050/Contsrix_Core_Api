<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Company\CompanyCore\Models\Company;

class ContractorSeeder extends Seeder
{
    /**
     * Seed the predefined list of contractors for all companies.
     */
    public function run(): void
    {
        // When running inside a tenant context, only seed the current tenant.
        // Otherwise, seed every company that exists in the central database.
        if (tenancy()->initialized) {
            $companyIds = [tenant()->getTenantKey()];
        } else {
            $companyIds = Company::query()->pluck('id')->all();
        }

        if (empty($companyIds)) {
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

        $now = now();
        $rows = [];

        foreach ($companyIds as $companyId) {
            foreach ($contractors as $index => $name) {
                $rows[] = [
                    'id'         => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'name'       => $name,
                    'number'     => 'CNT-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Use insertOrIgnore so re-running the seeder never triggers the
        // contractors_company_name_unique duplicate-key error.
        DB::table('contractors')->insertOrIgnore($rows);

        $this->command->info('Contractors seeded successfully: ' . count($contractors) . ' contractors x ' . count($companyIds) . ' companies.');
    }
}
