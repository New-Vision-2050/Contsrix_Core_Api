<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\MedicalInsurance\DTO\CreateMedicalInsuranceSubscriptionDTO;
use Modules\MedicalInsurance\DTO\CreateMedicalInsuranceSubscriptionFamilyMemberDTO;

class CreateMedicalInsuranceSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subscriptions'                                      => 'required|array|min:1',
            'subscriptions.*.user_id'                           => 'required|uuid|exists:users,id',
            'subscriptions.*.medical_insurance_id'              => 'required|uuid|exists:medical_insurances,id',
            'subscriptions.*.medical_insurance_category_id'     => 'nullable|uuid|exists:medical_insurance_categories,id',
            'subscriptions.*.amount'                            => 'required|numeric|min:0',
            'subscriptions.*.subscription_no'                   => 'required|string|max:255|distinct|unique:medical_insurance_subscriptions,subscription_no',
            'subscriptions.*.subscription_type'                 => 'required|string|in:individual,family',
            'subscriptions.*.status'                            => 'nullable|integer|in:-1,0,1',
            'subscriptions.*.family_members'                    => 'nullable|array',
            'subscriptions.*.family_members.*.name'             => 'required_with:subscriptions.*.family_members|string|max:255',
            'subscriptions.*.family_members.*.national_id'      => 'required_with:subscriptions.*.family_members|string|max:50',
            'subscriptions.*.family_members.*.relation'         => 'required_with:subscriptions.*.family_members|string|max:100',
            'subscriptions.*.family_members.*.amount'           => 'required_with:subscriptions.*.family_members|numeric|min:0',
            'subscriptions.*.family_members.*.subscription_no'  => 'nullable|string|max:255',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->get('subscriptions', []) as $index => $sub) {
                $subscriptionType = $sub['subscription_type'] ?? 'individual';
                $hasFamilyMembers = !empty($sub['family_members']);

                // individual cannot have dependents
                if ($subscriptionType === 'individual' && $hasFamilyMembers) {
                    $validator->errors()->add(
                        "subscriptions.{$index}.family_members",
                        __('Individual subscriptions cannot have family members.')
                    );
                }

                // Reject users with fixed (constant) insurance type
                if (!empty($sub['user_id'])) {
                    $hasFixedPrivilege = \Modules\UserInfo\UserPrivilege\Models\UserPrivilege::where('global_id', function ($q) use ($sub) {
                        $q->select('global_company_user_id')
                            ->from('users')
                            ->where('id', $sub['user_id']);
                    })
                    ->where('type_allowance_code', 'constant')
                    ->exists();

                    if ($hasFixedPrivilege) {
                        $validator->errors()->add(
                            "subscriptions.{$index}.user_id",
                            __('Employees with fixed insurance type cannot be added to medical insurance.')
                        );
                    }
                }
            }
        });
    }

    /**
     * @return array<CreateMedicalInsuranceSubscriptionDTO>
     */
    public function createDTOs(): array
    {
        return array_map(function (array $sub) {
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
                userId: $sub['user_id'],
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
