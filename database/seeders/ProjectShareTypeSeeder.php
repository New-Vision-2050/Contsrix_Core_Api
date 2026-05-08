<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Shared\ResourceShare\Models\ProjectShareType;

class ProjectShareTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Check if already seeded
        if (ProjectShareType::count() > 0) {
            $this->command->info('⚠ Project share types already seeded, skipping...');
            return;
        }

        // Types (النوع)
        $types = [
            ['ar' => 'جهة حكومية', 'en' => 'Government Entity'],
            ['ar' => 'جهة مالكة', 'en' => 'Owner Entity'],
            ['ar' => 'استشاري', 'en' => 'Consultant'],
            ['ar' => 'مقاول رئيسي', 'en' => 'Main Contractor'],
            ['ar' => 'مقاول باطن', 'en' => 'Subcontractor'],
            ['ar' => 'مورد', 'en' => 'Supplier'],
            ['ar' => 'شريك', 'en' => 'Partner'],
            ['ar' => 'إدارة داخلية', 'en' => 'Internal Management'],
            ['ar' => 'جهة رقابية', 'en' => 'Supervisory Entity'],
        ];

        foreach ($types as $type) {
            DB::table('project_share_types')->insert([
                'name' => json_encode($type),
                'level' => 'type',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Relations (العلاقة)
        $relations = [
            ['ar' => 'مرخصة لجميع المراحل', 'en' => 'Licensed for All Stages'],
            ['ar' => 'شريك استراتيجي', 'en' => 'Strategic Partner'],
            ['ar' => 'مشرف هندسي', 'en' => 'Engineering Supervisor'],
            ['ar' => 'مقاول عام', 'en' => 'General Contractor'],
            ['ar' => 'مقاول فرعي', 'en' => 'Sub Contractor'],
            ['ar' => 'مورد مواد', 'en' => 'Material Supplier'],
            ['ar' => 'ممول مشروع', 'en' => 'Project Financer'],
            ['ar' => 'إدارة فنية', 'en' => 'Technical Management'],
            ['ar' => 'مدقق خارجي', 'en' => 'External Auditor'],
        ];

        foreach ($relations as $relation) {
            DB::table('project_share_types')->insert([
                'name' => json_encode($relation),
                'level' => 'relation',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Roles (الدور)
        $roles = [
            ['ar' => 'جهة رقابية', 'en' => 'Regulatory Authority'],
            ['ar' => 'مستثمر رئيسي', 'en' => 'Main Investor'],
            ['ar' => 'مشرف هندسي', 'en' => 'Engineering Supervisor'],
            ['ar' => 'مقاول عام', 'en' => 'General Contractor'],
            ['ar' => 'مقاول فرعي', 'en' => 'Sub Contractor'],
            ['ar' => 'مورد مواد', 'en' => 'Material Supplier'],
            ['ar' => 'ممول مشروع', 'en' => 'Project Financer'],
            ['ar' => 'إدارة فنية', 'en' => 'Technical Management'],
            ['ar' => 'مدقق خارجي', 'en' => 'External Auditor'],
        ];

        foreach ($roles as $role) {
            DB::table('project_share_types')->insert([
                'name' => json_encode($role),
                'level' => 'role',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✓ Project share types seeded successfully');
    }
}
