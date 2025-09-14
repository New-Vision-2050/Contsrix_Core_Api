<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Leave\LeaveType\Models\LeaveType;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

class LeaveTypeBranchSeeder extends Seeder
{
    /**
     * Assign all branches to all leave types for the current company
     */
    public function run(): void
    {
        // Get current company ID from tenant context
        $companyId = tenant('id');
        
        if (!$companyId) {
            $this->command->warn('No tenant company found. Skipping LeaveTypeBranchSeeder.');
            return;
        }

        // Get all leave types for this company
        $leaveTypes = LeaveType::where('company_id', $companyId)->get();
        
        if ($leaveTypes->isEmpty()) {
            $this->command->info('No leave types found for company. Skipping branch assignment.');
            return;
        }

        // Get all branches for this company
        $branches = ManagementHierarchy::where('company_id', $companyId)
            ->where('type', 'branch')
            ->get();
            
        if ($branches->isEmpty()) {
            $this->command->info('No branches found for company. Skipping branch assignment.');
            return;
        }

        $this->command->info("Found {$leaveTypes->count()} leave types and {$branches->count()} branches for company ID: {$companyId}");

        // Assign all branches to all leave types
        foreach ($leaveTypes as $leaveType) {
            $branchIds = $branches->pluck('id')->toArray();
            
            // Sync branches to leave type (this will add only new relationships)
            $leaveType->branches()->syncWithoutDetaching($branchIds);
            
            $this->command->info("Assigned {$branches->count()} branches to leave type: {$leaveType->name}");
        }

        $this->command->info('Successfully assigned all branches to all leave types.');
    }
}
