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
            'employee_id' => 'required|uuid|exists:users,id',
            'end_date' => 'nullable|date|after_or_equal:today',
            'status' => 'nullable|integer|in:-1,0,1',
        ];
    }

    public function createUpdateMedicalInsuranceCommand(): UpdateMedicalInsuranceCommand
    {
        return new UpdateMedicalInsuranceCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            policyNumber: $this->get('policy_number'),
            employeeId: $this->get('employee_id'),
            endDate: $this->get('end_date'),
            status: $this->get('status') ? (int)$this->get('status') : null,
        );
    }
}
