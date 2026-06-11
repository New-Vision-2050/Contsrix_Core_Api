<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\MedicalInsurance\DTO\CreateMedicalInsuranceSubscriptionDTO;
use Modules\MedicalInsurance\DTO\CreateMedicalInsuranceSubscriptionFamilyMemberDTO;
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
            'subscriptions'                                     => 'nullable|array',
            'subscriptions.*.medical_insurance_id'              => 'required_with:subscriptions|uuid|exists:medical_insurances,id',
            'subscriptions.*.medical_insurance_category_id'     => 'nullable|uuid|exists:medical_insurance_categories,id',
            'subscriptions.*.amount'                            => 'required_with:subscriptions|numeric|min:0',
            'subscriptions.*.subscription_no'                   => 'required_with:subscriptions|string|max:255|distinct',
            'subscriptions.*.subscription_type'                 => 'nullable|string|in:individual,family',
            'subscriptions.*.status'                            => 'nullable|integer|in:-1,0,1',
            'subscriptions.*.family_members'                    => 'nullable|array',
            'subscriptions.*.family_members.*.name'             => 'required_with:subscriptions.*.family_members|string|max:255',
            'subscriptions.*.family_members.*.national_id'      => 'required_with:subscriptions.*.family_members|string|max:50',
            'subscriptions.*.family_members.*.relation'         => 'required_with:subscriptions.*.family_members|string|max:100',
            'subscriptions.*.family_members.*.amount'           => 'required_with:subscriptions.*.family_members|numeric|min:0',
            'subscriptions.*.family_members.*.subscription_no'  => 'nullable|string|max:255',
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
        );
    }

    /**
     * Build subscription DTOs from the request.
     *
     * @return array<CreateMedicalInsuranceSubscriptionDTO>
     */
    public function createSubscriptionDTOs(string $userId): array
    {
        return array_map(function (array $sub) use ($userId) {
            $familyMembers = array_map(
                fn (array $member) => new CreateMedicalInsuranceSubscriptionFamilyMemberDTO(
                    name: $member['name'],
                    nationalId: $member['national_id'],
                    relation: $member['relation'],
                    amount: (float) $member['amount'],
                    subscriptionNo: $member['subscription_no'] ?? null,
                ),
                $sub['family_members'] ?? []
            );

            return new CreateMedicalInsuranceSubscriptionDTO(
                userId: $userId,
                medicalInsuranceId: $sub['medical_insurance_id'],
                amount: (float) $sub['amount'],
                subscriptionNo: $sub['subscription_no'],
                medicalInsuranceCategoryId: $sub['medical_insurance_category_id'] ?? null,
                status: (int) ($sub['status'] ?? 1),
                subscriptionType: $sub['subscription_type'] ?? 'individual',
                familyMembers: $familyMembers,
            );
        }, $this->get('subscriptions', []));
    }
}
