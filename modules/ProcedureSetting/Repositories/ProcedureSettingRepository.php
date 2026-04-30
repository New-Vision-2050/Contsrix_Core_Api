<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\WorkFlow;
use App\Traits\HasExport;

/**
 * @property ProcedureSetting $model
 * @method ProcedureSetting findOneOrFail($id)
 * @method ProcedureSetting findOneByOrFail(array $data)
 */
class ProcedureSettingRepository extends BaseRepository
{
    use HasExport;

    public function __construct(ProcedureSetting $model)
    {
        parent::__construct($model);
    }

    public function getProcedureSettingList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getProcedureSetting(UuidInterface $id): ProcedureSetting
    {
        return $this->model->with([
            'steps.branch',
            'steps.management',
            'steps.escalationUser:id,name,email,phone',
            'steps.actionTakers.user',
            'steps.concernedUsers.user',
            'escalationUser:id,name,email,phone',
            'workFlow:id,name,company_id',
        ])->findOrFail($id->toString());
    }

    public function createProcedureSetting(array $data): ProcedureSetting
    {
        $companyId = $data['company_id'] ?? tenant('id');
        $procedureType = (string) ($data['type'] ?? '');
        $hasExplicitWorkFlow = isset($data['work_flow_id'])
            && $data['work_flow_id'] !== null
            && $data['work_flow_id'] !== '';

        if (! $hasExplicitWorkFlow && $companyId !== null && $companyId !== '') {
            $data['work_flow_id'] = WorkFlow::defaultForCompany((string) $companyId, $procedureType)->id;
        }

        $model = $this->create($data);
        $model->load(['escalationUser:id,name,email,phone', 'workFlow:id,name']);

        return $model;
    }

    /**
     * @param array<string, mixed> $conditions
     */
    public function list(array $conditions = [], string $orderBy = 'id', string $sortBy = 'asc'): Collection
    {
        return $this->model->newQuery()
            ->where($conditions)
            ->with(['escalationUser:id,name,email,phone', 'workFlow:id,name'])
            ->orderBy($orderBy, $sortBy)
            ->get();
    }

    public function listByWorkFlow(array $filters = []): \Illuminate\Support\Collection
    {
        $branchId = null;
        if (! empty($filters['branch_id'])) {
            $branchId = (int) $filters['branch_id'];
        }

        $query = WorkFlow::query()
            ->with([
                'managementHierarchies:id,name,type,company_id',
                'procedureSettings.escalationUser:id,name,email,phone',
                'procedureSettings.workFlow:id,name,company_id',
            ]);

        if (! empty($filters['type'])) {
            $query->where('type', (string) $filters['type']);
        }
        if (! empty($filters['work_flow_id'])) {
            $query->where('id', (string) $filters['work_flow_id']);
        }
        if ($branchId !== null) {
            $query->whereHas('managementHierarchies', function ($q) use ($branchId): void {
                $q->where('management_hierarchies.id', $branchId);
            });
        }

        return $query->orderBy('name')->orderBy('type')->get();
    }

    public function firstByWorkFlowFilters(array $filters = []): ?WorkFlow
    {
        $query = $this->listByWorkFlow($filters);

        if (! empty($filters['branch_id']) && empty($filters['type']) && empty($filters['work_flow_id'])) {
            $preferred = $query->firstWhere('type', ProcedureSettingType::ClientRequest->value);
            if ($preferred instanceof WorkFlow) {
                return $preferred;
            }
        }

        $first = $query->first();

        return $first instanceof WorkFlow ? $first : null;
    }

    public function getDefaultWorkFlowForList(): ?WorkFlow
    {
        $companyId = tenant('id');

        if ($companyId !== null && $companyId !== '') {
            WorkFlow::defaultForCompany((string) $companyId, ProcedureSettingType::ClientRequest->value);
        }

        $query = WorkFlow::query()
            ->with([
                'managementHierarchies:id,name,type,company_id',
                'procedureSettings.escalationUser:id,name,email,phone',
                'procedureSettings.workFlow:id,name,company_id',
            ])
            ->where('type', ProcedureSettingType::ClientRequest->value);

        if ($companyId !== null && $companyId !== '') {
            $query->where('company_id', (string) $companyId);
        }

        return $query->orderBy('name')->first();
    }

    public function getDefaultWorkFlowByType(string $type): ?WorkFlow
    {
        $companyId = tenant('id');

        if ($companyId !== null && $companyId !== '') {
            WorkFlow::defaultForCompany((string) $companyId, $type);
        }

        $query = WorkFlow::query()
            ->with([
                'managementHierarchies:id,name,type,company_id',
                'procedureSettings.escalationUser:id,name,email,phone',
                'procedureSettings.workFlow:id,name,company_id',
            ])
            ->where('type', $type)
            ->where('name', 'default');

        if ($companyId !== null && $companyId !== '') {
            $query->where('company_id', (string) $companyId);
        }

        return $query->orderBy('id')->first();
    }

    public function toggleBranchDefaultWorkFlows(int $branchId, bool $checked, string $type): ?WorkFlow
    {
        $branch = ManagementHierarchy::query()
            ->where('id', $branchId)
            ->where('type', 'branch')
            ->whereNotNull('company_id')
            ->firstOrFail(['id', 'company_id']);

        DB::transaction(function () use ($branch, $checked, $type): void {
            $companyId = (string) $branch->company_id;

            $workflowIdsForType = WorkFlow::query()
                ->where('company_id', $companyId)
                ->where('type', $type)
                ->pluck('id')
                ->all();

            if ($workflowIdsForType !== []) {
                DB::table('management_hierarchy_work_flow')
                    ->where('management_hierarchy_id', (int) $branch->id)
                    ->whereIn('work_flow_id', $workflowIdsForType, 'and', false)
                    ->delete();
            }

            if ($checked) {
                $default = WorkFlow::defaultForCompany($companyId, $type);

                DB::table('management_hierarchy_work_flow')->insertOrIgnore([
                    'management_hierarchy_id' => (int) $branch->id,
                    'work_flow_id'            => $default->id,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);

                return;
            }

            $branchSpecific = WorkFlow::query()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'type'       => $type,
                    'name'       => 'branch_' . $branch->id,
                ],
                ['id' => (string) \Illuminate\Support\Str::uuid()]
            );

            DB::table('management_hierarchy_work_flow')->insertOrIgnore([
                'management_hierarchy_id' => (int) $branch->id,
                'work_flow_id'            => $branchSpecific->id,
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);
        });

        $companyId = (string) $branch->company_id;

        if ($checked) {
            return WorkFlow::query()
                ->with([
                    'managementHierarchies:id,name,type,company_id',
                    'procedureSettings.escalationUser:id,name,email,phone',
                    'procedureSettings.workFlow:id,name,company_id',
                ])
                ->where('company_id', $companyId)
                ->where('type', $type)
                ->where('name', 'default')
                ->whereHas('managementHierarchies', function ($q) use ($branch): void {
                    $q->where('management_hierarchies.id', (int) $branch->id);
                })
                ->orderBy('id')
                ->first();
        }

        return WorkFlow::query()
            ->with([
                'managementHierarchies:id,name,type,company_id',
                'procedureSettings.escalationUser:id,name,email,phone',
                'procedureSettings.workFlow:id,name,company_id',
            ])
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->where('name', 'branch_' . $branch->id)
            ->whereHas('managementHierarchies', function ($q) use ($branch): void {
                $q->where('management_hierarchies.id', (int) $branch->id);
            })
            ->orderBy('id')
            ->first();
    }

    public function updateProcedureSetting(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProcedureSetting(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
