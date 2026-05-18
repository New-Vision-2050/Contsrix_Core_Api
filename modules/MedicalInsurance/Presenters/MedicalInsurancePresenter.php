<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Presenters;

use Modules\MedicalInsurance\Models\MedicalInsurance;
use BasePackage\Shared\Presenters\AbstractPresenter;

class MedicalInsurancePresenter extends AbstractPresenter
{
    private MedicalInsurance $medicalInsurance;

    public function __construct(MedicalInsurance $medicalInsurance)
    {
        $this->medicalInsurance = $medicalInsurance;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->medicalInsurance->id,
            'name' => $this->medicalInsurance->name,
            'policy_number' => $this->medicalInsurance->policy_number,
            'provider' => $this->medicalInsurance->provider,
            'employee_id' => $this->medicalInsurance->employee_id,
            'employee' => $this->medicalInsurance->employee ? [
                'id' => $this->medicalInsurance->employee->id,
                'name' => $this->medicalInsurance->employee->name,
                'email' => $this->medicalInsurance->employee->email,
                'phone' => $this->medicalInsurance->employee->phone,
            ] : null,
            'start_date' => $this->medicalInsurance->start_date?->format('Y-m-d'),
            'end_date' => $this->medicalInsurance->end_date?->format('Y-m-d'),
            'value' => $this->medicalInsurance->value,
            'individuals_count' => $this->medicalInsurance->individuals_count,
            'status' => $this->medicalInsurance->status,
            'created_at' => $this->medicalInsurance->created_at?->toDateTimeString(),
            'updated_at' => $this->medicalInsurance->updated_at?->toDateTimeString(),
        ];
    }
}
