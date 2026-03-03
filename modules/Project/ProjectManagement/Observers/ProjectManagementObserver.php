<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Observers;

use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Illuminate\Support\Facades\DB;

class ProjectManagementObserver
{
    /**
     * Handle the ProjectManagement "creating" event.
     */
    public function creating(ProjectManagement $project): void
    {
        // Generate serial number only if it's not already set
        if (is_null($project->serial_number)) {
            $project->serial_number = $this->generateSerialNumber($project->company_id);
        }
    }

    /**
     * Generate a unique serial number for the project.
     * Format: PRJ-{company_code}-{number} (e.g., PRJ-COMP1-0001)
     */
    private function generateSerialNumber(string $companyId): string
    {
        // Get the maximum serial number for this company
        $maxSerial = DB::table('projects')
            ->where('company_id', $companyId)
            ->where('serial_number', 'like', 'PRJ-%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(serial_number, \'-\', -1) AS UNSIGNED) DESC')
            ->value('serial_number');

        if ($maxSerial) {
            // Extract numeric part (after last dash) and increment
            $parts = explode('-', $maxSerial);
            $numericPart = (int) end($parts);
            $newNumeric = $numericPart + 1;
        } else {
            $newNumeric = 1;
        }

        // Create a short company code from the company ID (first 4 chars)
        $companyCode = strtoupper(substr($companyId, 0, 4));
        
        // Format with leading zeros (4 digits)
        return 'PRJ-' . $companyCode . '-' . str_pad((string)$newNumeric, 4, '0', STR_PAD_LEFT);
    }
}
