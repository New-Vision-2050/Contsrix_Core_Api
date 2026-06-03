<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\Privilege\Services\PrivilegeCardConfigService;
use Modules\UserInfo\UserPrivilege\Commands\UpdateUserPrivilegeCommand;
use Modules\UserInfo\UserPrivilege\Models\UserPrivilege;
use Ramsey\Uuid\Uuid;

class UpdateUserPrivilegeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type_privilege_id'    => 'nullable|string',
            'type_allowance_code'  => 'nullable|string|not_in:percentage',
            'charge_amount'        => 'nullable|string',
            'description'          => 'nullable|string',
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

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $typeAllowanceCode = $this->get('type_allowance_code');

            // If changing to fixed (constant), check the user has no medical insurance subscriptions
            if ($typeAllowanceCode === 'constant') {
                $userPrivilege = UserPrivilege::find($this->route('id'));

                if ($userPrivilege) {
                    $userId = \Modules\User\Models\User::where('global_company_user_id', $userPrivilege->global_id)
                        ->where('company_id', $userPrivilege->company_id)
                        ->value('id');

                    if ($userId) {
                        $hasSubscription = \Modules\MedicalInsurance\Models\MedicalInsuranceSubscription::where('user_id', $userId)
                            ->exists();

                        if ($hasSubscription) {
                            $validator->errors()->add(
                                'type_allowance_code',
                                __('Cannot change insurance type to fixed while the employee has active medical insurance subscriptions. Remove them from medical insurance first.')
                            );
                        }
                    }
                }
            }
        });
    }

    public function createUpdateUserPrivilegeCommand(): UpdateUserPrivilegeCommand
    {
        return new UpdateUserPrivilegeCommand(
            id: Uuid::fromString($this->route('id')),
            type_privilege_id: $this->get('type_privilege_id'),
            type_allowance_code: $this->get('type_allowance_code'),
            charge_amount: $this->get('charge_amount'),
            description: $this->get('description'),
            period_id: $this->get('period_id'),
            medical_insurance_id: $this->get('medical_insurance_id'),
        );
    }

    /**
     * Resolve the privilege type from the existing user_privilege record.
     */
    private function privilegeType(): ?string
    {
        $id = $this->route('id');

        if (! $id) {
            return null;
        }

        $userPrivilege = UserPrivilege::with('privilege')->find($id);

        return $userPrivilege?->privilege?->type;
    }
}
