<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Observers;

use Modules\Subscription\Package\Models\Package;
use Illuminate\Support\Facades\Log;

class PackageObserver
{
    /**
     * Handle the Package "updating" event.
     *
     * @param Package $package
     * @return bool|null
     */
    public function updating(Package $package): ?bool
    {
        // Block direct updates to packages named "Main Package"
        if ($package->name === 'Main Package' && $this->isDirectUpdate()) {
            Log::warning("Attempted to directly update Main Package", [
                'package_id' => $package->id,
                'package_name' => $package->name
            ]);
            
            throw new \Exception("The 'Main Package' cannot be updated directly. It is managed automatically by the system.");
        }
        
        return true;
    }

    /**
     * Handle the Package "deleting" event.
     *
     * @param Package $package
     * @return bool|null
     */
    public function deleting(Package $package): ?bool
    {
        // Block deletion of packages named "Main Package"
        if ($package->name === 'Main Package') {
            Log::warning("Attempted to delete Main Package", [
                'package_id' => $package->id,
                'package_name' => $package->name
            ]);
            
            throw new \Exception("The 'Main Package' cannot be deleted. It is managed automatically by the system.");
        }

        // Block deletion of packages assigned to any company
        $companiesCount = $package->companies()->count();
        if ($companiesCount > 0) {
            Log::warning("Attempted to delete package assigned to companies", [
                'package_id' => $package->id,
                'package_name' => $package->name,
                'companies_count' => $companiesCount
            ]);
            
            throw new \Exception("Cannot delete package '{$package->name}' because it is assigned to {$companiesCount} company/companies. Please remove the package assignments first.");
        }

        Log::info("Package deletion allowed - no companies assigned", [
            'package_id' => $package->id,
            'package_name' => $package->name
        ]);
        
        return true;
    }

    /**
     * Check if this is a direct update (not from system/auto-update)
     * 
     * @return bool
     */
    private function isDirectUpdate(): bool
    {
        // Check if the update is coming from our auto-update system
        // We'll set a flag in the request or use a different approach
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        
        // If the update is coming from our listener, allow it
        foreach ($trace as $frame) {
            if (isset($frame['class']) && 
                str_contains($frame['class'], 'CreateMainPackageListener') ||
                str_contains($frame['class'], 'UpdateMainPackageListener')) {
                return false; // This is a system update, allow it
            }
        }
        
        return true; // This is a direct update, block it
    }
}
