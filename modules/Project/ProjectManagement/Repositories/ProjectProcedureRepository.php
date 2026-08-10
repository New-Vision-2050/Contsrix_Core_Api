<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
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
        ?string $parentProcedureSettingId = null,
        ?string $ownerCompanyId = null,
        ?string $readerCompanyId = null
    ): Collection {
        return $this->baseProjectQuery($projectId, $procedureType, $parentProcedureSettingId, $ownerCompanyId, $readerCompanyId)
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
        ?string $parentProcedureSettingId = null,
        ?string $ownerCompanyId = null,
        ?string $readerCompanyId = null
    ): ProjectProcedureSetting {
        return $this->baseProjectQuery($projectId, $procedureType, $parentProcedureSettingId, $ownerCompanyId, $readerCompanyId)
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
            'attachmentType:id,name,parent_id,project_id,company_id',
            'attachmentSubType:id,name,parent_id,project_id,company_id',
            'attachmentSubSubType:id,name,parent_id,project_id,company_id',
            'jobAttribute:id,name,code,is_active',
            'receiverCompanies',
        ]);
    }

    private function baseProjectQuery(
        string $projectId,
        string $procedureType,
        ?string $parentProcedureSettingId = null,
        ?string $ownerCompanyId = null,
        ?string $readerCompanyId = null
    ): Builder {
        $companyId = $ownerCompanyId ?? (string) (tenant('id') ?? '');
        $readerCompanyId ??= (string) (tenant('id') ?? '');

        $query = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->where('company_id', $companyId)
            ->whereHas('procedureSetting', static function ($query) use (
                $projectId,
                $procedureType,
                $parentProcedureSettingId,
                $companyId
            ): void {
                $query->withoutGlobalScopes()
                    ->where('type', $procedureType)
                    ->where('company_id', $companyId)
                    ->whereHas('workFlow', static function ($query) use ($projectId, $procedureType, $companyId): void {
                        $query->withoutGlobalScopes()
                            ->where('project_id', $projectId)
                            ->where('type', $procedureType)
                            ->where('company_id', $companyId);
                    });

                if ($parentProcedureSettingId !== null) {
                    $query->where('parent_id', $parentProcedureSettingId);
                }
            })
            ->with([
                'project',
                'procedureSetting.escalationManagementHierarchy:id,name,type,company_id',
                'procedureSetting.workFlow:id,name,company_id,type',
                'attachmentType:id,name,parent_id,project_id,company_id',
                'attachmentSubType:id,name,parent_id,project_id,company_id',
                'attachmentSubSubType:id,name,parent_id,project_id,company_id',
                'jobAttribute:id,name,code,is_active',
                'receiverCompanies',
            ]);

        if ($readerCompanyId !== '' && $readerCompanyId !== $companyId) {
            $query->whereExists(static function ($query) use ($projectId, $companyId, $readerCompanyId): void {
                $query->selectRaw('1')
                    ->from('resource_shares')
                    ->where('shareable_type', ProjectManagement::class)
                    ->whereColumn('shareable_id', 'project_procedure_settings.project_id')
                    ->where('shareable_id', $projectId)
                    ->where('owner_company_id', $companyId)
                    ->where('shared_with_company_id', $readerCompanyId)
                    ->where('status', 'accepted');
            })
                ->where(static function (Builder $query) use ($readerCompanyId): void {
                    $query->whereNotExists(static function ($query): void {
                        $query->selectRaw('1')
                            ->from('project_procedure_setting_receiver_companies')
                            ->whereColumn(
                                'project_procedure_setting_receiver_companies.project_procedure_setting_id',
                                'project_procedure_settings.id'
                            );
                    })
                        ->orWhereExists(static function ($query) use ($readerCompanyId): void {
                            $query->selectRaw('1')
                                ->from('project_procedure_setting_receiver_companies')
                                ->whereColumn(
                                    'project_procedure_setting_receiver_companies.project_procedure_setting_id',
                                    'project_procedure_settings.id'
                                )
                                ->where('project_procedure_setting_receiver_companies.company_id', $readerCompanyId);
                        });
                });
        }

        return $query;
    }
}
