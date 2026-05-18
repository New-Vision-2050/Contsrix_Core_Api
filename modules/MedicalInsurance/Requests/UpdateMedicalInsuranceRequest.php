<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\MedicalInsurance\Commands\UpdateMedicalInsuranceCommand;
use Modules\MedicalInsurance\Handlers\UpdateMedicalInsuranceHandler;

class UpdateMedicalInsuranceRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'policy_number' => 'required|string|max:255|unique:medical_insurances,policy_number,' . $id,
            'provider' => 'nullable|string|max:255',
            'employee_id' => 'nullable|uuid|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'value' => 'nullable|numeric|min:0',
            'individuals_count' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|in:-1,0,1',
        ];
    }

    public function createUpdateMedicalInsuranceCommand(): UpdateMedicalInsuranceCommand
    {
        return new UpdateMedicalInsuranceCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            policyNumber: $this->get('policy_number'),
            provider: $this->get('provider'),
            employeeId: $this->get('employee_id'),
            startDate: $this->get('start_date'),
            endDate: $this->get('end_date'),
            value: $this->get('value') !== null ? (float) $this->get('value') : null,
            individualsCount: $this->get('individuals_count') !== null ? (int) $this->get('individuals_count') : null,
            status: $this->get('status') !== null ? (int) $this->get('status') : null,
        );
    }
}
