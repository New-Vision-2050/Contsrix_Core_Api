<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\Privilege\Models\Privilege;
use Modules\Shared\Privilege\Services\PrivilegeCardConfigService;
use Modules\UserInfo\UserPrivilege\DTO\CreateUserPrivilegeDTO;

class CreateUserPrivilegeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id'              => 'required|string',
            'type_privilege_id'    => 'required|string',
            'type_allowance_code'  => 'required|string|not_in:percentage',
            'charge_amount'        => 'nullable|string',
            'description'          => 'nullable|string',
            'privilege_id'         => 'required|string',
            'period_id'            => 'nullable|string',
            'medical_insurance_id' => [
                'nullable',
                'uuid',
                'exists:medical_insurances,id',
                Rule::requiredIf(function () {
                    return $this->privilegeType() === PrivilegeCardConfigService::TYPE_HEALTH_INSURANCE;
                }),
            ],
        ];
    }

    public function createCreateUserPrivilegeDTO(): CreateUserPrivilegeDTO
    {
        return new CreateUserPrivilegeDTO(
            company_id: '',
            global_id: '',
            type_privilege_id: $this->get('type_privilege_id'),
            type_allowance_code: $this->get('type_allowance_code'),
            charge_amount: $this->get('charge_amount'),
            description: $this->get('description'),
            privilege_id: $this->get('privilege_id'),
            period_id: $this->get('period_id'),
            medical_insurance_id: $this->get('medical_insurance_id'),
        );
    }

    /**
     * Resolve the privilege type from the provided privilege_id.
     */
    private function privilegeType(): ?string
    {
        $privilegeId = $this->input('privilege_id');

        if (! $privilegeId) {
            return null;
        }

        $privilege = Privilege::find($privilegeId);

        return $privilege?->type;
    }
}
