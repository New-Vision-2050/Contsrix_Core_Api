<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\MedicalInsurance\Commands\UpdateMedicalInsuranceSubscriptionCommand;
use Ramsey\Uuid\Uuid;

class UpdateMedicalInsuranceSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'user_id'                          => 'required|uuid|exists:users,id',
            'medical_insurance_id'             => 'required|uuid|exists:medical_insurances,id',
            'medical_insurance_category_id'    => 'nullable|uuid|exists:medical_insurance_categories,id',
            'amount'                           => 'required|numeric|min:0',
            'subscription_no'                  => 'required|string|max:255|unique:medical_insurance_subscriptions,subscription_no,' . $id,
            'status'                           => 'nullable|integer|in:-1,0,1',
            'family_members'                   => 'nullable|array',
            'family_members.*.name'            => 'required_with:family_members|string|max:255',
            'family_members.*.national_id'     => 'required_with:family_members|string|max:50',
            'family_members.*.relation'        => 'required_with:family_members|string|max:100',
            'family_members.*.amount'          => 'required_with:family_members|numeric|min:0',
            'family_members.*.subscription_no' => 'nullable|string|max:255',
        ];
    }

    public function createCommand(): UpdateMedicalInsuranceSubscriptionCommand
    {
        $familyMembers = array_map(
            fn (array $member) => [
                'name'            => $member['name'],
                'national_id'     => $member['national_id'],
                'relation'        => $member['relation'],
                'amount'          => (float) $member['amount'],
                'subscription_no' => $member['subscription_no'] ?? null,
            ],
            $this->get('family_members', []) ?? []
        );

        return new UpdateMedicalInsuranceSubscriptionCommand(
            id: Uuid::fromString($this->route('id')),
            userId: $this->get('user_id'),
            medicalInsuranceId: $this->get('medical_insurance_id'),
            amount: (float) $this->get('amount'),
            subscriptionNo: $this->get('subscription_no'),
            medicalInsuranceCategoryId: $this->get('medical_insurance_category_id'),
            status: (int) $this->get('status', 1),
            familyMembers: $familyMembers,
        );
    }
}
