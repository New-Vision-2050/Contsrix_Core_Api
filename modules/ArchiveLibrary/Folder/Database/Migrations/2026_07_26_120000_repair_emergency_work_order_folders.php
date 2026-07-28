<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Project\ProjectManagement\Services\ProjectNotificationService;

/**
 * The emergency work order hierarchy used to resolve its project root folder by
 * `project_id` alone. Because every folder in the hierarchy carries that same
 * `project_id`, the lookup could return a descendant and the whole hierarchy was
 * then rebuilt inside itself, producing trees such as:
 *
 *   اوامر عمل الطوارئ → 43916512 → اوامر عمل الطوارئ → 44179553
 *
 * This migration flattens those trees back to one emergency folder per project,
 * renames it to its final name and marks the hierarchy as system managed.
 */
return new class extends Migration
{
    private const LEGACY_NAMES = [
        'اوامر عمل الطوارئ',
        'أوامر عمل الطوارئ',
    ];

    public function up(): void
    {
        $names = array_merge(self::LEGACY_NAMES, [ProjectNotificationService::MAINTENANCE_EMERGENCY_FOLDER_NAME]);

        $groups = DB::table('folders')
            ->whereIn('name', $names)
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($folder) => $folder->company_id . '|' . ($folder->project_id ?? ''));

        foreach ($groups as $folders) {
            $this->repairGroup($folders->values()->all());
        }
    }

    public function down(): void
    {
        // The duplicated folders cannot be recreated; only the rename is reversible.
        DB::table('folders')
            ->where('name', ProjectNotificationService::MAINTENANCE_EMERGENCY_FOLDER_NAME)
            ->update(['name' => self::LEGACY_NAMES[0]]);
    }

    /**
     * @param array<int, object> $folders every emergency folder of one project
     */
    private function repairGroup(array $folders): void
    {
        $companyId = $folders[0]->company_id;
        $projectId = $folders[0]->project_id;
        $rootId = $this->findProjectRootId($projectId, $companyId);

        $canonical = $this->pickCanonical($folders, $rootId);

        foreach ($folders as $folder) {
            if ($folder->id !== $canonical->id) {
                $this->mergeFolder($folder->id, $canonical->id);
            }
        }

        DB::table('folders')->where('id', $canonical->id)->update([
            'name' => ProjectNotificationService::MAINTENANCE_EMERGENCY_FOLDER_NAME,
            'parent_id' => $rootId,
            'type' => Folder::TYPE_SYSTEM,
            'updated_at' => now(),
        ]);

        $this->markSubtreeAsSystem($canonical->id);
    }

    /**
     * Prefer the folder that already sits directly under the project root,
     * otherwise the oldest one, which is the outermost of the nested copies.
     *
     * @param array<int, object> $folders
     */
    private function pickCanonical(array $folders, ?string $rootId): object
    {
        foreach ($folders as $folder) {
            if ($folder->parent_id === $rootId) {
                return $folder;
            }
        }

        return $folders[0];
    }

    private function findProjectRootId(?string $projectId, string $companyId): ?string
    {
        if (! $projectId) {
            return null;
        }

        $root = DB::table('folders')
            ->where('id', $projectId)
            ->whereNull('parent_id')
            ->first();

        $root ??= DB::table('folders')
            ->where('project_id', $projectId)
            ->where('company_id', $companyId)
            ->whereNull('parent_id')
            ->orderBy('created_at')
            ->first();

        return $root?->id;
    }

    /**
     * Move everything below $sourceId under $targetId, merging same named
     * subfolders instead of duplicating them, then drop the empty source.
     */
    private function mergeFolder(string $sourceId, string $targetId): void
    {
        if ($sourceId === $targetId || $this->isAncestorOf($sourceId, $targetId)) {
            return;
        }

        $children = DB::table('folders')->where('parent_id', $sourceId)->get();

        foreach ($children as $child) {
            $twin = DB::table('folders')
                ->where('parent_id', $targetId)
                ->where('name', $child->name)
                ->first();

            if ($twin && $twin->id !== $child->id) {
                $this->mergeFolder($child->id, $twin->id);

                continue;
            }

            DB::table('folders')->where('id', $child->id)->update([
                'parent_id' => $targetId,
                'updated_at' => now(),
            ]);
        }

        DB::table('files')->where('folder_id', $sourceId)->update([
            'folder_id' => $targetId,
            'updated_at' => now(),
        ]);

        DB::table('user_folder_permissions')->where('folder_id', $sourceId)->delete();
        DB::table('folders')->where('id', $sourceId)->delete();
    }

    private function isAncestorOf(string $ancestorId, string $folderId): bool
    {
        $seen = [];
        $currentId = $folderId;

        while ($currentId !== null && ! isset($seen[$currentId])) {
            $seen[$currentId] = true;

            $parentId = DB::table('folders')->where('id', $currentId)->value('parent_id');

            if ($parentId === $ancestorId) {
                return true;
            }

            $currentId = $parentId;
        }

        return false;
    }

    private function markSubtreeAsSystem(string $folderId, int $depth = 0): void
    {
        if ($depth > 20) {
            return;
        }

        $childIds = DB::table('folders')->where('parent_id', $folderId)->pluck('id');

        if ($childIds->isEmpty()) {
            return;
        }

        DB::table('folders')->whereIn('id', $childIds)->update([
            'type' => Folder::TYPE_SYSTEM,
            'updated_at' => now(),
        ]);

        foreach ($childIds as $childId) {
            $this->markSubtreeAsSystem((string) $childId, $depth + 1);
        }
    }
};
