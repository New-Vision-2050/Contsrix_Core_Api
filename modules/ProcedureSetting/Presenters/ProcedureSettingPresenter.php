<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Presenters;

use Modules\ProcedureSetting\Models\ProcedureSetting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProcedureSettingPresenter extends AbstractPresenter
{
    private ProcedureSetting $procedureSetting;

    public function __construct(ProcedureSetting $procedureSetting)
    {
        $this->procedureSetting = $procedureSetting;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id'           => $this->procedureSetting->id,
            'name'         => $this->procedureSetting->name,
            'type'         => $this->procedureSetting->type,
            'execute_type' => $this->procedureSetting->execute_type,
            'icon'         => $this->procedureSetting->icon,
            'percentage'   => $this->procedureSetting->percentage,
        ];

        if (!$isListing && $this->procedureSetting->relationLoaded('steps')) {
            $data['steps'] = $this->procedureSetting->steps->map(function ($step) {
                return [
                    'id'                   => $step->id,
                    'employee_id'          => $step->employee_id,
                    'is_accept'            => $step->is_accept,
                    'is_approve'           => $step->is_approve,
                    'duration'             => $step->duration,
                    'forms'                => $step->forms, // 'approve', 'accept', or 'financial'
                    'procedure_setting_id' => $step->procedure_setting_id,
                ];
            })->toArray();
        }

        return $data;
    }
}
