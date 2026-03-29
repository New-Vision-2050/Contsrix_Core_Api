<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Presenters;

use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProcedureSettingStepPresenter extends AbstractPresenter
{
    private ProcedureSettingStep $step;

    public function __construct(ProcedureSettingStep $step)
    {
        $this->step = $step;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id'                   => $this->step->id,
            'procedure_setting_id' => $this->step->procedure_setting_id,
            'employee_id'          => $this->step->employee_id,
            'is_accept'            => $this->step->is_accept,
            'is_approve'           => $this->step->is_approve,
            'duration'             => $this->step->duration,
            'forms'                => $this->step->forms, // 'approve', 'accept', or 'financial'
        ];

        if ($this->step->relationLoaded('employee') && $this->step->employee) {
            $data['employee'] = [
                'id'    => $this->step->employee->id,
                'name'  => $this->step->employee->name,
                'email' => $this->step->employee->email,
            ];
        }

        return $data;
    }
}
