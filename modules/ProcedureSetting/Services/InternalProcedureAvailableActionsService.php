<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Services;

use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

/**
 * Generic, module-agnostic service that resolves which internal procedures
 * (available actions) are visible for any processable entity.
 *
 * Filtering rules applied:
 *  - is_active = true
 *  - appears_after_id:  ALL referenced procedures must be "taken" (else hidden)
 *  - appears_before_id: if ANY referenced procedure is "taken", this is hidden
 *
 * "Taken" status is read centrally from internal_procedure_takens via
 * ProcedureWorkflowService::getTakenProcedureIds().
 *
 * Any module can call forProcessable() without duplicating filtering logic.
 */
final class InternalProcedureAvailableActionsService
{
    public function __construct(
        private readonly ProcedureWorkflowService $workflowService,
    ) {}

    /**
     * @return list<array{
     *     id: string,
     *     name: string,
     *     form: array{key: string, label_ar: string}|null,
     *     conditions: array,
     *     appears_before_ids: list<string>,
     *     appears_after_ids: list<string>,
     *     sort_order: int|null,
     * }>
     */
    public function forProcessable(
        string $processableType,
        string $processableId,
        string $procedureCategoryType,
        string $companyId,
        ?string $branchId,
        ?string $parentSettingId = null,
    ): array {
        $parentSettingId = $parentSettingId ?? $this->resolveParentSettingId($procedureCategoryType, $companyId, $branchId);

        if ($parentSettingId === null) {
            return [];
        }

        $items = ProcedureSetting::query()
            ->where('parent_id', $parentSettingId)
            ->whereNotNull('form')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $takenIds = $this->workflowService->getTakenProcedureIds($processableType, $processableId);

        return $items
            ->filter(function (ProcedureSetting $setting) use ($takenIds): bool {
                $afterIds  = $setting->appears_after_id  ?? [];
                $beforeIds = $setting->appears_before_id ?? [];

                // ALL prerequisites must be taken
                foreach ($afterIds as $id) {
                    if (! in_array($id, $takenIds, true)) {
                        return false;
                    }
                }

                // Hide once ANY "before" guard has been taken
                foreach ($beforeIds as $id) {
                    if (in_array($id, $takenIds, true)) {
                        return false;
                    }
                }

                return true;
            })
            ->map(function (ProcedureSetting $setting): array {
                $form = $setting->form ? InternalProcessForm::tryFrom($setting->form) : null;
                return [
                    'id'                 => $setting->id,
                    'name'               => $setting->name,
                    'form'               => $form !== null ? [
                        'key'      => $form->value,
                        'label_ar' => $form->labelAr(),
                    ] : null,
                    'conditions'         => $setting->conditions ?? [],
                    'appears_before_ids' => $setting->appears_before_id ?? [],
                    'appears_after_ids'  => $setting->appears_after_id  ?? [],
                    'sort_order'         => $setting->sort_order,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Finds the parent ProcedureSetting ID for a given category type, company,
     * and optional branch. Falls back to the company-default if no branch match.
     */
    private function resolveParentSettingId(
        string $procedureCategoryType,
        string $companyId,
        ?string $branchId,
    ): ?string {
        $query = ProcedureSetting::query()
            ->whereNull('parent_id')
            ->where('type', $procedureCategoryType)
            ->where('company_id', $companyId);

        if ($branchId !== null) {
            $query->whereHas('workFlow', function ($q) use ($branchId): void {
                $q->whereHas('managementHierarchies', function ($q) use ($branchId): void {
                    $q->where('management_hierarchies.id', $branchId);
                });
            });
        }

        return $query->orderBy('sort_order')->value('id');
    }
}
