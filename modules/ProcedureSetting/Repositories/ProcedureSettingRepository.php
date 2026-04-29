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
        $branchIds = [];
        if (! empty($filters['branch_id']) && is_array($filters['branch_id'])) {
            $branchIds = array_values(array_unique(array_map('intval', $filters['branch_id'])));
            $this->ensureWorkFlowsForBranches($branchIds);
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
        if ($branchIds !== []) {
            $query->whereHas('managementHierarchies', function ($q) use ($branchIds): void {
                $q->whereIn('management_hierarchies.id', $branchIds, 'and', false);
            });
        }

        return $query->orderBy('name')->orderBy('type')->get();
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

    /**
     * Ensure each provided branch is linked to default workflows for all procedure types.
     *
     * @param list<int> $branchIds
     */
    private function ensureWorkFlowsForBranches(array $branchIds): void
    {
        if ($branchIds === []) {
            return;
        }

        $branches = ManagementHierarchy::query()
            ->whereIn('id', $branchIds, 'and', false)
            ->where('type', 'branch')
            ->whereNotNull('company_id')
            ->get(['id', 'company_id']);

        foreach ($branches as $branch) {
            $workFlowIds = [];
            foreach (ProcedureSettingType::cases() as $type) {
                $workFlowIds[] = WorkFlow::defaultForCompany((string) $branch->company_id, $type->value)->id;
            }

            $now = now();
            $rows = [];
            foreach ($workFlowIds as $workFlowId) {
                $rows[] = [
                    'management_hierarchy_id' => (int) $branch->id,
                    'work_flow_id'            => $workFlowId,
                    'created_at'              => $now,
                    'updated_at'              => $now,
                ];
            }

            if ($rows !== []) {
                DB::table('management_hierarchy_work_flow')->insertOrIgnore($rows);
            }
        }
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
