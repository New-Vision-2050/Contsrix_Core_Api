<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Support\Facades\DB;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Services\ProjectArchiveFolderService;
use Modules\Project\ProjectManagement\Services\ProjectNotificationService;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;

/**
 * Archive Library folders for Construction work orders (اوامر العمل / الانشاءات).
 *
 * Hierarchy (same project root parent as Emergency/Maintenance):
 *   Project folder → "الانشاءات" → {ProjectOrderPermit.name}
 *
 * Folders are created empty; file storage follows later via the same Archive path.
 */
class ConstructionArchiveFolderService
{
    public const CONSTRUCTION_FOLDER_NAME = 'الانشاءات';

    public function __construct(
        private readonly ProjectArchiveFolderService $archiveFolders,
    ) {}

    /**
     * Ensure the Construction category folder and an empty work-order folder
     * named after the permit exist under the project root.
     */
    public function ensureWorkOrderFolder(ProjectOrderPermit $permit): ?Folder
    {
        $name = trim((string) $permit->name);
        if ($name === '') {
            return null;
        }

        $companyId = $this->resolveCompanyId($permit);
        if ($companyId === null) {
            return null;
        }

        $projectId = (string) $permit->project_id;

        return DB::transaction(function () use ($name, $companyId, $projectId) {
            $constructionFolder = $this->ensureConstructionCategoryFolder($projectId, $companyId);
            if ($constructionFolder === null) {
                return null;
            }

            return $this->archiveFolders->findOrCreateSubfolder(
                name: $name,
                parentId: $constructionFolder->id,
                companyId: $companyId,
                projectId: $projectId,
            );
        });
    }

    /**
     * Ensure Construction category + one empty folder per existing work order
     * for a project. Idempotent.
     *
     * @return array{construction_folder: Folder|null, work_order_folders: int}
     */
    public function ensureProjectWorkOrderFolders(string $projectId, ?string $companyId = null): array
    {
        $companyId ??= $this->resolveCompanyIdForProject($projectId);
        if ($companyId === null) {
            return ['construction_folder' => null, 'work_order_folders' => 0];
        }

        $permits = ProjectOrderPermit::query()
            ->withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->get(['id', 'name', 'project_id']);

        if ($permits->isEmpty()) {
            return ['construction_folder' => null, 'work_order_folders' => 0];
        }

        return DB::transaction(function () use ($projectId, $companyId, $permits) {
            $constructionFolder = $this->ensureConstructionCategoryFolder($projectId, $companyId);
            if ($constructionFolder === null) {
                return ['construction_folder' => null, 'work_order_folders' => 0];
            }

            $createdOrFound = 0;
            foreach ($permits as $permit) {
                $name = trim((string) $permit->name);
                if ($name === '') {
                    continue;
                }

                $this->archiveFolders->findOrCreateSubfolder(
                    name: $name,
                    parentId: $constructionFolder->id,
                    companyId: $companyId,
                    projectId: $projectId,
                );
                $createdOrFound++;
            }

            return [
                'construction_folder' => $constructionFolder,
                'work_order_folders' => $createdOrFound,
            ];
        });
    }

    /**
     * Keep the work-order folder name in sync when a permit is renamed.
     */
    public function syncWorkOrderFolderName(
        ProjectOrderPermit $permit,
        string $oldName,
        string $newName,
    ): void {
        $oldName = trim($oldName);
        $newName = trim($newName);

        if ($oldName === '' || $newName === '' || $oldName === $newName) {
            return;
        }

        $companyId = $this->resolveCompanyId($permit);
        if ($companyId === null) {
            return;
        }

        $projectId = (string) $permit->project_id;

        DB::transaction(function () use ($oldName, $newName, $companyId, $projectId) {
            $constructionFolder = $this->ensureConstructionCategoryFolder($projectId, $companyId);
            if ($constructionFolder === null) {
                return;
            }

            $existingNew = Folder::query()
                ->withoutTenancy()
                ->where('name', $newName)
                ->where('parent_id', $constructionFolder->id)
                ->where('company_id', $companyId)
                ->where('project_id', $projectId)
                ->lockForUpdate()
                ->first();

            if ($existingNew) {
                return;
            }

            $oldFolder = Folder::query()
                ->withoutTenancy()
                ->where('name', $oldName)
                ->where('parent_id', $constructionFolder->id)
                ->where('company_id', $companyId)
                ->where('project_id', $projectId)
                ->lockForUpdate()
                ->first();

            if ($oldFolder) {
                $oldFolder->forceFill([
                    'name' => $newName,
                    'type' => Folder::TYPE_SYSTEM,
                ])->save();

                return;
            }

            $this->archiveFolders->findOrCreateSubfolder(
                name: $newName,
                parentId: $constructionFolder->id,
                companyId: $companyId,
                projectId: $projectId,
            );
        });
    }

    /**
     * Construction and Emergency must share the same parent (project root).
     */
    public function ensureConstructionCategoryFolder(string $projectId, string $companyId): ?Folder
    {
        $projectFolder = $this->archiveFolders->findProjectRootFolder($projectId, $companyId);

        return $this->archiveFolders->findOrCreateSubfolder(
            name: self::CONSTRUCTION_FOLDER_NAME,
            parentId: $projectFolder?->id,
            companyId: $companyId,
            projectId: $projectId,
        );
    }

    public function findConstructionCategoryFolder(string $projectId, string $companyId): ?Folder
    {
        $projectFolder = $this->archiveFolders->findProjectRootFolder($projectId, $companyId);

        $query = Folder::query()
            ->withoutTenancy()
            ->where('name', self::CONSTRUCTION_FOLDER_NAME)
            ->where('company_id', $companyId)
            ->where('project_id', $projectId);

        if ($projectFolder?->id) {
            $query->where('parent_id', $projectFolder->id);
        } else {
            $query->whereNull('parent_id');
        }

        return $query->first();
    }

    /**
     * Assert helper for tests / verification: Emergency and Construction parents match.
     */
    public function shareSameParentAsEmergency(string $projectId, string $companyId): bool
    {
        $projectFolder = $this->archiveFolders->findProjectRootFolder($projectId, $companyId);
        if ($projectFolder === null) {
            return false;
        }

        $emergency = Folder::query()
            ->withoutTenancy()
            ->where('name', ProjectNotificationService::MAINTENANCE_EMERGENCY_FOLDER_NAME)
            ->where('parent_id', $projectFolder->id)
            ->where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->first();

        $construction = $this->findConstructionCategoryFolder($projectId, $companyId);

        if ($emergency === null || $construction === null) {
            return false;
        }

        return $emergency->parent_id === $construction->parent_id;
    }

    private function resolveCompanyId(ProjectOrderPermit $permit): ?string
    {
        return $this->resolveCompanyIdForProject((string) $permit->project_id);
    }

    private function resolveCompanyIdForProject(string $projectId): ?string
    {
        $project = ProjectManagement::query()
            ->withoutGlobalScopes()
            ->find($projectId);

        $companyId = $project?->company_id;

        return $companyId !== null && $companyId !== '' ? (string) $companyId : null;
    }
}
