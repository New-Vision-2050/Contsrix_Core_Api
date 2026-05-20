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
                familyMembers: $familyMembers,
            );
        }, $this->get('subscriptions', []));
    }
}
