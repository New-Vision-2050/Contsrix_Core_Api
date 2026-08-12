<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Modules\ArchiveLibrary\Folder\Models\Folder;

/**
 * Shared find-or-create helpers for the project Archive Library tree.
 *
 * Used by Maintenance/Emergency (الصيانه و الطوارئ) and Construction (الانشاءات)
 * so both categories resolve the same project root parent.
 */
class ProjectArchiveFolderService
{
    /**
     * Resolve the root folder of a project (created by ProjectManagementObserver,
     * which reuses the project id as the folder id).
     *
     * Every folder in a project hierarchy also carries the same project_id,
     * so the lookup must be anchored on the folder id / a null parent_id.
     * Matching on project_id alone would return an arbitrary descendant and
     * nest a new hierarchy inside the previous one.
     */
    public function findProjectRootFolder(?string $projectId, string $companyId): ?Folder
    {
        if (! $projectId) {
            return null;
        }

        return Folder::query()
            ->withoutTenancy()
            ->where('id', $projectId)
            ->whereNull('parent_id')
            ->first()
            ?? Folder::query()
                ->withoutTenancy()
                ->where('project_id', $projectId)
                ->where('company_id', $companyId)
                ->whereNull('parent_id')
                ->orderBy('created_at')
                ->first();
    }

    /**
     * Find or create a subfolder by name under a given parent folder.
     *
     * Filters by project_id (in addition to name/parent/company) so that
     * projects lacking a root folder never collide on a shared root-level
     * folder. Must be called within a DB::transaction(); uses lockForUpdate()
     * to reduce (not eliminate, since there is no unique DB constraint) the
     * chance of concurrent requests creating duplicate folders.
     */
    public function findOrCreateSubfolder(
        string $name,
        ?string $parentId,
        string $companyId,
        ?string $projectId = null,
    ): Folder {
        $query = Folder::query()
            ->withoutTenancy()
            ->where('name', $name)
            ->where('company_id', $companyId)
            ->lockForUpdate();

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        } else {
            $query->whereNull('project_id');
        }

        $folder = $query->first();

        if ($folder) {
            if ($folder->type !== Folder::TYPE_SYSTEM) {
                $folder->forceFill(['type' => Folder::TYPE_SYSTEM])->save();
            }

            return $folder;
        }

        return Folder::create([
            'name' => $name,
            'parent_id' => $parentId,
            'project_id' => $projectId,
            'company_id' => $companyId,
            'access_type' => 'public',
            'status' => 1,
            'type' => Folder::TYPE_SYSTEM,
        ]);
    }
}
