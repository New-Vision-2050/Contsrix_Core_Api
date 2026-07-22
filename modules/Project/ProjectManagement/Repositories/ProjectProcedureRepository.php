<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;

/**
 * @property ProjectProcedureSetting $model
 */
class ProjectProcedureRepository extends BaseRepository
{
    public function __construct(ProjectProcedureSetting $model)
    {
        parent::__construct($model);
    }

    public function listForProject(
        string $projectId,
        string $procedureType,
        ?string $parentProcedureSettingId = null
    ): Collection {
        return $this->baseProjectQuery($projectId, $procedureType, $parentProcedureSettingId)
            ->get()
            ->sortBy(static fn (ProjectProcedureSetting $item): string => sprintf(
                '%010d-%s',
                (int) ($item->procedureSetting?->sort_order ?? 0),
                (string) ($item->procedureSetting?->name ?? '')
            ))
            ->values();
    }

    public function findForProject(
        string $projectId,
        string $procedureSettingId,
        string $procedureType,
        ?string $parentProcedureSettingId = null
    ): ProjectProcedureSetting {
        return $this->baseProjectQuery($projectId, $procedureType, $parentProcedureSettingId)
            ->where('procedure_setting_id', $procedureSettingId)
            ->firstOrFail();
    }

    public function createProjectProcedure(array $data): ProjectProcedureSetting
    {
        /** @var ProjectProcedureSetting $item */
        $item = $this->create($data);

        return $this->loadRelations($item);
    }

    public function updateProjectProcedure(ProjectProcedureSetting $item, array $data): ProjectProcedureSetting
    {
        $item->fill($data);
        $item->save();

        return $this->loadRelations($item);
    }

    public function loadRelations(ProjectProcedureSetting $item): ProjectProcedureSetting
    {
        return $item->load([
            'project',
            'procedureSetting.escalationManagementHierarchy:id,name,type,company_id',
            'procedureSetting.workFlow:id,name,company_id,type',
            'receiverCompany',
            'attachmentType:id,name,parent_id,project_id,company_id',
            'attachmentSubType:id,name,parent_id,project_id,company_id',
            'attachmentSubSubType:id,name,parent_id,project_id,company_id',
            'jobAttribute:id,name,code,is_active',
        ]);
    }

    private function baseProjectQuery(
        string $projectId,
        string $procedureType,
        ?string $parentProcedureSettingId = null
    ): Builder {
        return $this->model->newQuery()
            ->where('project_id', $projectId)
            ->whereHas('procedureSetting', static function ($query) use (
                $projectId,
                $procedureType,
                $parentProcedureSettingId
            ): void {
                $query->where('type', $procedureType)
                    ->where('company_id', tenant('id'))
                    ->whereHas('workFlow', static function ($query) use ($projectId, $procedureType): void {
                        $query->where('project_id', $projectId)
                            ->where('type', $procedureType);
                    });

                if ($parentProcedureSettingId !== null) {
                    $query->where('parent_id', $parentProcedureSettingId);
                }
            })
            ->with([
                'project',
                'procedureSetting.escalationManagementHierarchy:id,name,type,company_id',
                'procedureSetting.workFlow:id,name,company_id,type',
                'receiverCompany',
                'attachmentType:id,name,parent_id,project_id,company_id',
                'attachmentSubType:id,name,parent_id,project_id,company_id',
                'attachmentSubSubType:id,name,parent_id,project_id,company_id',
                'jobAttribute:id,name,code,is_active',
            ]);
    }
}
