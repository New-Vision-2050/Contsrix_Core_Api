<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

class RecalculateUsersCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recalculate:users-count 
                            {--dry-run : Show what would be updated without making changes}
                            {--hierarchy-id= : Recalculate for specific management hierarchy ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate users_count recursively for management hierarchies and sync to branches/managements tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $hierarchyId = $this->option('hierarchy-id');

        $this->info('Starting recursive users_count recalculation...');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            DB::beginTransaction();

            // Step 1: Recalculate management_hierarchies users_count recursively
            $this->info('Step 1: Recalculating management_hierarchies users_count recursively...');
            
            if ($hierarchyId) {
                $hierarchy = ManagementHierarchy::findOrFail($hierarchyId);
                $this->recalculateHierarchyUsersCount($hierarchy, $isDryRun);
            } else {
                // Get all management hierarchies ordered by path length (deepest first)
                // This ensures child counts are calculated before parent counts
                $hierarchies = ManagementHierarchy::orderByRaw('CHAR_LENGTH(path) DESC')->get();
                
                $updated = 0;
                foreach ($hierarchies as $hierarchy) {
                    if ($this->recalculateHierarchyUsersCount($hierarchy, $isDryRun)) {
                        $updated++;
                    }
                }
                
                if (!$isDryRun) {
                    $this->info("Updated {$updated} management hierarchy records");
                }
            }

            // Step 2: Sync to branches table
            $this->info('Step 2: Syncing users_count to branches table...');
            $this->syncToBranchesTable($hierarchyId, $isDryRun);

            // Step 3: Sync to managements table  
            $this->info('Step 3: Syncing users_count to managements table...');
            $this->syncToManagementsTable($hierarchyId, $isDryRun);

            // Step 4: Show summary
            if (!$isDryRun) {
                DB::commit();
                $this->info('✅ Recursive users count recalculation completed successfully!');
            } else {
                DB::rollBack();
                $this->info('✅ Dry run completed - no changes were made');
                $this->showDryRunSummary($hierarchyId);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during recalculation: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Recalculate users count for a specific hierarchy (includes all descendants)
     */
    private function recalculateHierarchyUsersCount(ManagementHierarchy $hierarchy, bool $isDryRun): bool
    {
        // Get all descendant hierarchy IDs (including self)
        $hierarchyIds = ManagementHierarchy::query()
            ->whereSelfOrDescendantOf($hierarchy)
            ->pluck('id')
            ->toArray();
        
        // Count all users in this hierarchy tree
        $actualCount = DB::table('users')
            ->whereIn('management_hierarchy_id', $hierarchyIds)
            ->whereNull('deleted_at')
            ->count();
        
        if ($hierarchy->users_count != $actualCount) {
            if (!$isDryRun) {
                $hierarchy->users_count = $actualCount;
                $hierarchy->save();
            }
            return true;
        }
        
        return false;
    }

    /**
     * Sync users_count to branches table
     */
    private function syncToBranchesTable(?int $hierarchyId, bool $isDryRun): void
    {
        $query = "
            UPDATE branches b 
            INNER JOIN management_hierarchies mh ON b.management_hierarchy_id = mh.id 
            SET b.users_count = mh.users_count
        ";
        
        if ($hierarchyId) {
            $query .= " WHERE mh.id = ?";
            if (!$isDryRun) {
                $affected = DB::update($query, [$hierarchyId]);
                $this->info("Updated {$affected} branch records");
            }
        } else {
            if (!$isDryRun) {
                $affected = DB::update($query);
                $this->info("Updated {$affected} branch records");
            }
        }
    }

    /**
     * Sync users_count to managements table
     */
    private function syncToManagementsTable(?int $hierarchyId, bool $isDryRun): void
    {
        $query = "
            UPDATE managements m 
            INNER JOIN management_hierarchies mh ON m.management_hierarchy_id = mh.id 
            SET m.users_count = mh.users_count
        ";
        
        if ($hierarchyId) {
            $query .= " WHERE mh.id = ?";
            if (!$isDryRun) {
                $affected = DB::update($query, [$hierarchyId]);
                $this->info("Updated {$affected} management records");
            }
        } else {
            if (!$isDryRun) {
                $affected = DB::update($query);
                $this->info("Updated {$affected} management records");
            }
        }
    }

    /**
     * Show dry run summary
     */
    private function showDryRunSummary(?int $hierarchyId): void
    {
        $hierarchies = ManagementHierarchy::when($hierarchyId, function($query, $id) {
                return $query->where('id', $id);
            })
            ->get();

        $changesNeeded = [];
        
        foreach ($hierarchies as $hierarchy) {
            // Get all descendant hierarchy IDs (including self)
            $hierarchyIds = ManagementHierarchy::query()
                ->whereSelfOrDescendantOf($hierarchy)
                ->pluck('id')
                ->toArray();
            
            // Count all users in this hierarchy tree
            $actualCount = DB::table('users')
                ->whereIn('management_hierarchy_id', $hierarchyIds)
                ->whereNull('deleted_at')
                ->count();
            
            if ($hierarchy->users_count != $actualCount) {
                $changesNeeded[] = [
                    'id' => $hierarchy->id,
                    'name' => $hierarchy->name,
                    'type' => $hierarchy->type,
                    'current_count' => $hierarchy->users_count,
                    'actual_count' => $actualCount,
                    'descendants' => count($hierarchyIds) - 1
                ];
            }
        }
        
        if (count($changesNeeded) > 0) {
            $this->table(
                ['ID', 'Name', 'Type', 'Current Count', 'Actual Count', 'Descendants'],
                array_map(function($row) {
                    return [$row['id'], $row['name'], $row['type'], $row['current_count'], $row['actual_count'], $row['descendants']];
                }, $changesNeeded)
            );
        } else {
            $this->info('All users_count values are already accurate!');
        }
    }
}
