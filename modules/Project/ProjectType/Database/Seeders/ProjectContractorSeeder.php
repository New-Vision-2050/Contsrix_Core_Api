<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectManagement\Models\Contractor;
use Modules\Project\ProjectManagement\Models\ContractorRepresentative;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Illuminate\Support\Facades\DB;

class ProjectContractorSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = tenant('id');

        // Get first project to assign (optional; can be null)
        $project = ProjectManagement::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->first();

        $projectId = $project?->id;

        // ---------- Contractor 1 ----------
        $contractor1 = Contractor::create([
            'company_id'            => $companyId,
            'project_id'            => $projectId,
            'name'                  => 'شركة النور للمقاولات',
            'tax_card'              => 'TAX-001',
            'commercial_register'   => 'CR-001',
            'activity'              => 'أعمال إنشائية',
            'email'                 => 'info@alnour.com',
            'country_id'            => null, // يمكن تحديد دولة حقيقية إن وجدت
            'project_contractor_id' => 'CONT-001',
            'project_manager_name'  => 'محمد عبد الله',
            'project_manager_phone' => '01012345678',
            'project_manager_nationality' => 'مصري',
            'project_manager_email' => 'mohamed@alnour.com',
            'is_active'             => true,
        ]);

        // Add representatives for contractor 1
        ContractorRepresentative::create([
            'contractor_id' => $contractor1->id,
            'name'          => 'أحمد سعيد',
            'mobile'        => '0111111111',
            'nationality'   => 'مصري',
        ]);
        ContractorRepresentative::create([
            'contractor_id' => $contractor1->id,
            'name'          => 'سارة محمد',
            'mobile'        => '0122222222',
            'nationality'   => 'مصري',
        ]);

        // ---------- Contractor 2 (optional) ----------
        $contractor2 = Contractor::create([
            'company_id'            => $companyId,
            'project_id'            => $projectId,
            'name'                  => 'شركة البناء الحديث',
            'tax_card'              => 'TAX-002',
            'commercial_register'   => 'CR-002',
            'activity'              => 'تشطيبات داخلية',
            'email'                 => 'info@modern.com',
            'country_id'            => null,
            'project_contractor_id' => 'CONT-002',
            'project_manager_name'  => 'علي حسن',
            'project_manager_phone' => '01098765432',
            'project_manager_nationality' => 'مصري',
            'project_manager_email' => 'ali@modern.com',
            'is_active'             => true,
        ]);

        // Add representative for contractor 2
        ContractorRepresentative::create([
            'contractor_id' => $contractor2->id,
            'name'          => 'خالد محمود',
            'mobile'        => '0133333333',
            'nationality'   => 'مصري',
        ]);

        $this->command->info('تم إضافة مقاولين تجريبيين بنجاح.');
    }
}
