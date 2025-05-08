<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Handlers;

use Illuminate\Support\Facades\DB;
use Modules\Company\ManagementHierarchy\Commands\UpdateDepartmentCommand;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

class UpdateDepartmentHandler
{
    public function handle(UpdateDepartmentCommand $command): void
    {
        try {
            DB::beginTransaction();
            
            // Update the department
            $department = ManagementHierarchy::findOrFail($command->getId());
            $department->update([
                'name' => $command->getName(),
                'parent_id' => $command->getBranchId(),
                'company_id' => $command->getCompanyId(),
                'is_active' => $command->getIsActive(),
                'manager_id' => $command->getManagerId(),
            ]);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
