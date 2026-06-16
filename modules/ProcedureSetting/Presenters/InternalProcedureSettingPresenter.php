<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Presenters;

use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

final class InternalProcedureSettingPresenter
{
    public function __construct(private readonly ProcedureSetting $setting) {}

    public function toArray(): array
    {
        $form = $this->setting->form ? InternalProcessForm::tryFrom($this->setting->form) : null;

        $data = [
            'id'                 => $this->setting->id,
            'parent_id'          => $this->setting->parent_id,
            'name'               => $this->setting->name,
            'type'               => $this->setting->type,
            'form'               => $form ? [
                'key'        => $form->value,
                'label_ar'   => $form->labelAr(),
                'conditions' => array_map(
                    static fn ($c) => $c->toDefinition(),
                    $form->conditions(),
                ),
            ] : null,
            'conditions'         => $this->setting->conditions ?? [],
            'appears_before_id'  => $this->setting->appears_before_id,
            'appears_after_id'   => $this->setting->appears_after_id,
            'sort_order'         => $this->setting->sort_order,
            'execute_type'       => $this->setting->execute_type,
            'is_active'          => $this->setting->is_active,
            'percentage'         => $this->setting->percentage,
            'deadline_days'      => $this->setting->deadline_days,
            'deadline_hours'     => $this->setting->deadline_hours,
        ];

        if ($this->setting->relationLoaded('steps')) {
            $data['steps'] = $this->setting->steps->map(
                static fn ($step) => (new ProcedureSettingStepPresenter($step))->getData()
            )->values()->all();
        }

        return $data;
    }

    public static function single(ProcedureSetting $setting): array
    {
        return (new self($setting))->toArray();
    }

    public static function collection(iterable $settings): array
    {
        $result = [];
        foreach ($settings as $setting) {
            $result[] = (new self($setting))->toArray();
        }
        return $result;
    }
}
