<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\MedicalInsurance\DTO\CreateMedicalInsuranceDTO;

class CreateMedicalInsuranceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'policy_number' => 'required|string|max:255|unique:medical_insurances,policy_number',
            'employee_id' => 'required|uuid|exists:users,id',
            'status' => 'nullable|integer|in:-1,0,1',
        ];
    }

    public function createCreateMedicalInsuranceDTO(): CreateMedicalInsuranceDTO
    {
        return new CreateMedicalInsuranceDTO(
            name: $this->get('name'),
            policyNumber: $this->get('policy_number'),
            employeeId: $this->get('employee_id'),
            status:(int) $this->get('status', 1),
        );
    }
}
