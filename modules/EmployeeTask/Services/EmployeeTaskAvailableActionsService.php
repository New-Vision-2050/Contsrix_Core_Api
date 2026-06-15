<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

/**
 * Resolves which InternalProcedureSettings (available actions) the employee
 * can perform on a given task, respecting ordering (appears_before / appears_after)
 * and returning the sorted list.
 */
final class EmployeeTaskAvailableActionsService
{
    public function __construct(
        private readonly EmployeeTaskRepository $repository,
    ) {}

    /**
     * Returns an ordered list of InternalProcedureSettings available for the task.
     * Each item represents one action the employee may perform (start, extend, cancel…).
     *
     * @return list<array>
     */
    public function forTask(string $taskId): array
    {
        $task = $this->repository->findById($taskId);

        if (! $task) {
            throw EmployeeTaskException::notFound();
        }

        $procedureSettingId = $this->resolveProcedureSettingId($task);

        if ($procedureSettingId === null) {
            return [];
        }

        $items = ProcedureSetting::query()
            ->where('parent_id', $procedureSettingId)
            ->whereNotNull('form')
            ->orderBy('sort_order')
            ->get();

        return $items->map(function (ProcedureSetting $setting): array {
            $form = $setting->form ? InternalProcessForm::tryFrom($setting->form) : null;
            $conditions = $setting->conditions ?? [];

            return [
                'id'                 => $setting->id,
                'name'               => $setting->name,
                'form'               => $form ? [
                    'key'      => $form->value,
                    'label_ar' => $form->labelAr(),
                ] : null,
                'conditions'         => $conditions,
                'appears_before_id'  => $setting->appears_before_id,
                'appears_after_id'   => $setting->appears_after_id,
                'sort_order'         => $setting->sort_order,
            ];
        })->values()->all();
    }

    /**
     * Finds the parent procedure_setting_id for the task's company/branch.
     */
    private function resolveProcedureSettingId(EmployeeTaskRequest $task): ?string
    {
        $task->loadMissing('user.userProfessionalData');
        $branchId = $task->user?->userProfessionalData?->branch_id;

        $query = ProcedureSetting::query()
            ->whereNull('parent_id')
            ->where('type', ProcedureSettingType::EmployeeTask->value)
            ->where('company_id', $task->company_id);

        if ($branchId !== null) {
            $query->whereHas('workFlow', function ($q) use ($branchId) {
                $q->whereHas('managementHierarchies', function ($q) use ($branchId) {
                    $q->where('management_hierarchies.id', $branchId);
                });
            });
        }

        return $query->orderBy('sort_order')->value('id');
    }
}
