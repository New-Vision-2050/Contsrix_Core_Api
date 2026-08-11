<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Exceptions\ProcedureWorkflowException;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

final class InternalProcedureSettingService
{
    public function __construct(
        private readonly ProcedureSettingCloneService $cloneService,
    ) {}

    public function listForParent(string $parentId): Collection
    {
        return ProcedureSetting::query()
            ->where('parent_id', $parentId)
            ->whereNotNull('form')
            ->with(['steps' => fn ($q) => $q->orderBy('step_order'), 'steps.actionTakers.user'])
            ->orderBy('sort_order')
            ->get();
    }

    public function listAll(?string $type = null): Collection
    {
        $query = ProcedureSetting::query()
            ->whereNotNull('form')
            ->with(['steps' => fn ($q) => $q->orderBy('step_order'), 'steps.actionTakers.user'])
            ->orderBy('sort_order');

        if ($type !== null && $type !== '') {
            $query->where('type', $type);
        }

        return $query->get();
    }

    public function create(string $parentId, array $data): ProcedureSetting
    {
        return DB::transaction(function () use ($parentId, $data): ProcedureSetting {
            $parent = $this->findParentOrFail($parentId);
            $requestData = $data;
            $source = $this->sourceFromData($data, (string) $parent->company_id);

            if ($source instanceof ProcedureSetting) {
                $data = array_replace($this->sourceProcedureData($source), $data);
            }

            unset($data['source_procedure_setting_id']);

            $form = InternalProcessForm::tryFrom($data['form'] ?? '');

            if ($form === null) {
                throw new \InvalidArgumentException("Invalid form key: [{$data['form']}]");
            }

            $type = $data['type'] ?? $parent->type;

            if ($type !== $parent->type) {
                throw new \InvalidArgumentException(
                    "Provided type [{$type}] must match parent type [{$parent->type}]."
                );
            }

            if (! in_array($type, $form->applicableTypes(), true)) {
                throw new \InvalidArgumentException(
                    "Form [{$form->value}] is not applicable to procedure type [{$type}]."
                );
            }

            $existing = ProcedureSetting::query()
                ->where('parent_id', $parentId)
                ->where('form', $form->value)
                ->first();

            if ($existing !== null) {
                throw new \InvalidArgumentException(
                    "Internal procedure with form [{$form->value}] already exists under this parent."
                );
            }

            $conditions = $source instanceof ProcedureSetting
                ? ($source->conditions ?? [])
                : InternalProcessCondition::defaultValuesForForm($form);

            if (isset($requestData['conditions']) && is_array($requestData['conditions'])) {
                $incoming = $this->normalizeConditions($requestData['conditions']);
                // New rich format (indexed list) — use as-is; old flat assoc — merge with base
                $conditions = is_int(array_key_first($incoming) ?? null) ? $incoming : array_merge($conditions, $incoming);
            }

            $workFlow = $this->createWorkFlowForInternal($parent, $form->value, $type, $source);

            $setting = ProcedureSetting::query()->create([
                'id'                => (string) Str::uuid(),
                'company_id'        => $parent->company_id,
                'work_flow_id'      => $workFlow->id,
                'parent_id'         => $parent->id,
                'name'              => $data['name'] ?? $form->labelAr(),
                'form'              => $form->value,
                'type'              => $type,
                'is_active'         => $data['is_active'] ?? true,
                'execute_type'      => $data['execute_type'] ?? 'sequence',
                'conditions'        => $conditions,
                'appears_before_id' => $data['appears_before_id'] ?? null,
                'appears_after_id'  => $data['appears_after_id'] ?? null,
                'sort_order'        => $data['sort_order'] ?? $form->sortOrder(),
                'percentage'        => $data['percentage'] ?? 0,
                'deadline_days'     => $data['deadline_days'] ?? null,
                'deadline_hours'    => $data['deadline_hours'] ?? null,
            ]);

            if ($source instanceof ProcedureSetting) {
                $this->cloneService->duplicateSteps((string) $source->id, (string) $setting->id);
            }

            return $setting->fresh();
        });
    }

    public function update(string $parentId, string $id, array $data): ProcedureSetting
    {
        $parent = $this->findParentOrFail($parentId);
        $setting = $this->findChildOrFail($id, $parentId);

        if (isset($data['type']) && $data['type'] !== $parent->type) {
            throw new \InvalidArgumentException(
                "Provided type [{$data['type']}] must match parent type [{$parent->type}]."
            );
        }

        $update = [];

        foreach (['name', 'type', 'execute_type', 'sort_order', 'percentage', 'deadline_days', 'deadline_hours'] as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        if (array_key_exists('appears_before_id', $data)) {
            $update['appears_before_id'] = $data['appears_before_id'];
        }

        if (array_key_exists('appears_after_id', $data)) {
            $update['appears_after_id'] = $data['appears_after_id'];
        }

        if (array_key_exists('is_active', $data)) {
            $update['is_active'] = $data['is_active'];
        }

        if (array_key_exists('form', $data)) {
            $form = InternalProcessForm::tryFrom($data['form']);

            if ($form === null) {
                throw new \InvalidArgumentException("Invalid form key: [{$data['form']}]");
            }

            $type = $data['type'] ?? $setting->type;

            if (! in_array($type, $form->applicableTypes(), true)) {
                throw new \InvalidArgumentException(
                    "Form [{$form->value}] is not applicable to procedure type [{$type}]."
                );
            }

            $existing = ProcedureSetting::query()
                ->where('parent_id', $parentId)
                ->where('form', $form->value)
                ->where('id', '!=', $id)
                ->first();

            if ($existing !== null) {
                throw new \InvalidArgumentException(
                    "Internal procedure with form [{$form->value}] already exists under this parent."
                );
            }

            $update['form'] = $form->value;
        }

        if (isset($data['conditions']) && is_array($data['conditions'])) {
            $normalized = $this->normalizeConditions($data['conditions']);
            // New rich format (indexed list) — replace entirely; old flat assoc — merge with existing
            $update['conditions'] = is_int(array_key_first($normalized) ?? null)
                ? $normalized
                : array_merge($setting->conditions ?? [], $normalized);
        }

        $setting->update($update);
        $setting->loadMissing(['steps' => fn ($q) => $q->orderBy('step_order'), 'steps.actionTakers.user']);

        return $setting->fresh();
    }

    public function setStatus(string $parentId, string $id, bool $isActive): ProcedureSetting
    {
        $this->findParentOrFail($parentId);
        $setting = $this->findChildOrFail($id, $parentId);

        $setting->update(['is_active' => $isActive]);

        return $setting->fresh();
    }

    public function delete(string $parentId, string $id): void
    {
        $this->findParentOrFail($parentId);
        $setting = $this->findChildOrFail($id, $parentId);
        $setting->delete();
    }

    public function findParentByType(string $type, ?string $companyId = null): ?ProcedureSetting
    {
        // withoutGlobalScopes() bypasses BelongsToTenant so the parent can be
        // found even when the query tenant-context doesn't match the stored company_id.
        $query = ProcedureSetting::withoutGlobalScopes()
            ->whereNull('parent_id')
            ->whereNull('form')
            ->where('type', $type);

        if ($companyId !== null && $companyId !== '') {
            $query->where('company_id', $companyId);
        }

        return $query->orderBy('sort_order')->first();
    }

    public function availableFormsForParent(string $parentId): array
    {
        $parent = $this->findParentOrFail($parentId);

        return array_map(
            static fn (InternalProcessForm $form): array => $form->toDefinition(),
            InternalProcessForm::forType($parent->type),
        );
    }

    public function resolveByForm(string $parentProcedureSettingId, string $formKey): ?ProcedureSetting
    {
        return ProcedureSetting::query()
            ->where('parent_id', $parentProcedureSettingId)
            ->where('form', $formKey)
            ->whereNotNull('form')
            ->with(['steps' => fn ($q) => $q->orderBy('step_order')])
            ->first();
    }

    private function findParentOrFail(string $parentId): ProcedureSetting
    {
        $parent = ProcedureSetting::query()
            ->whereNull('parent_id')
            ->find($parentId);

        if (! $parent) {
            throw ProcedureWorkflowException::settingNotFound();
        }

        return $parent;
    }

    private function findChildOrFail(string $id, string $parentId): ProcedureSetting
    {
        $setting = ProcedureSetting::query()
            ->where('parent_id', $parentId)
            ->whereNotNull('form')
            ->find($id);

        if (! $setting) {
            throw ProcedureWorkflowException::settingNotFound();
        }

        return $setting;
    }

    private function nextSortOrder(string $parentId): int
    {
        return (int) ProcedureSetting::query()
            ->where('parent_id', $parentId)
            ->max('sort_order') + 1;
    }

    private function createWorkFlowForInternal(
        ProcedureSetting $parent,
        string $formValue,
        string $type,
        ?ProcedureSetting $source = null,
    ): WorkFlow
    {
        $workFlow = WorkFlow::query()->create([
            'id'         => (string) Str::uuid(),
            'company_id' => $parent->company_id,
            'name'       => $formValue,
            'type'       => $type,
        ]);

        $sourceWorkFlow = $source instanceof ProcedureSetting && $source->work_flow_id !== null
            ? WorkFlow::query()->withoutGlobalScopes()->find($source->work_flow_id)
            : null;

        // Copy branch associations from the source when cloning, otherwise from the parent workflow.
        $branchSource = $sourceWorkFlow ?? WorkFlow::query()->withoutGlobalScopes()->find($parent->work_flow_id);
        if ($branchSource !== null) {
            $branchIds = $branchSource->managementHierarchies()->pluck('management_hierarchies.id')->all();
            $workFlow->managementHierarchies()->syncWithoutDetaching($branchIds);
        }

        return $workFlow;
    }

    private function sourceFromData(array $data, string $companyId): ?ProcedureSetting
    {
        $sourceId = $data['source_procedure_setting_id'] ?? null;

        if (! is_string($sourceId) || $sourceId === '') {
            return null;
        }

        return ProcedureSetting::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('type', ProcedureSettingType::values())
            ->whereHas('workFlow', static function ($query): void {
                $query->withoutGlobalScopes()
                    ->whereNull('project_id');
            })
            ->findOrFail($sourceId);
    }

    /**
     * @return array<string, mixed>
     */
    private function sourceProcedureData(ProcedureSetting $source): array
    {
        $data = [];
        foreach ([
            'name',
            'type',
            'execute_type',
            'icon',
            'percentage',
            'deadline_days',
            'deadline_hours',
            'escalation_management_hierarchy_id',
            'sort_order',
            'form',
            'conditions',
            'appears_before_id',
            'appears_after_id',
            'is_active',
        ] as $key) {
            $data[$key] = $source->getAttribute($key);
        }

        return $data;
    }

    /**
     * Normalise conditions from various frontend formats:
     *  - New rich format (list):  [{key, is_active, sort_order, settings}]  → returned as-is
     *  - Old indexed format:      [{key: "AllowDuringShift", value: true}]  → assoc {allow_during_shift: true}
     *  - Already assoc flat:      {allow_during_shift: true}                → returned as-is
     */
    private function normalizeConditions(array $input): array
    {
        if ($input === []) {
            return [];
        }

        // Already an associative array (old flat format) → pass through
        if (! is_int(array_key_first($input))) {
            return $input;
        }

        // Indexed array — distinguish new rich format from old [{key, value}] format
        // New rich format items have 'is_active'; old format items have only 'key' + 'value'
        $firstItem = reset($input);
        if (is_array($firstItem) && array_key_exists('is_active', $firstItem)) {
            return $input; // new rich format — pass through as-is
        }

        // Old indexed format [{key: "AllowDuringShift", value: true}]
        $normalized = [];
        foreach ($input as $item) {
            if (! is_array($item) || ! array_key_exists('key', $item)) {
                continue;
            }
            $key      = (string) $item['key'];
            $value    = $item['value'] ?? null;
            $snakeKey = Str::snake($key);
            $enum     = InternalProcessCondition::tryFrom($snakeKey);

            if ($enum !== null) {
                $normalized[$enum->value] = $value;
            } else {
                $normalized[$snakeKey] = $value;
            }
        }

        return $normalized;
    }
}
