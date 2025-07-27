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
        // Block direct updates to main packages
        if ($package->is_main_package && $this->isDirectUpdate()) {
            Log::warning("Attempted to directly update main package", [
                'package_id' => $package->id,
                'package_name' => $package->name
            ]);
            
            throw new \Exception("Main packages cannot be updated directly. They are managed automatically by the system.");
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
        // Block deletion of main packages
        if ($package->is_main_package) {
            Log::warning("Attempted to delete main package", [
                'package_id' => $package->id,
                'package_name' => $package->name
            ]);
            
            throw new \Exception("Main packages cannot be deleted. They are managed automatically by the system.");
        }
        
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
