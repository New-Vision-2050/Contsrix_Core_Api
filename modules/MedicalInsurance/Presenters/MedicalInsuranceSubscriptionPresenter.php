<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\MedicalInsurance\Models\MedicalInsuranceSubscription;

class MedicalInsuranceSubscriptionPresenter extends AbstractPresenter
{
    private MedicalInsuranceSubscription $subscription;

    public function __construct(MedicalInsuranceSubscription $subscription)
    {
        $this->subscription = $subscription;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id'                   => $this->subscription->id,
            'user_id'              => $this->subscription->user_id,
            'medical_insurance_id' => $this->subscription->medical_insurance_id,
            'amount'               => $this->subscription->amount,
            'subscription_no'      => $this->subscription->subscription_no,
            'status'               => $this->subscription->status,
            'user'                 => $this->subscription->user ? [
                'id'   => $this->subscription->user->id,
                'name' => $this->subscription->user->name,
            ] : null,
            'medical_insurance'    => $this->subscription->medicalInsurance ? [
                'id'            => $this->subscription->medicalInsurance->id,
                'name'          => $this->subscription->medicalInsurance->name,
                'policy_number' => $this->subscription->medicalInsurance->policy_number,
            ] : null,
            'family_members'       => MedicalInsuranceSubscriptionFamilyMemberPresenter::collection(
                $this->subscription->familyMembers ?? []
            ),
            'medical_insurance_category' => $this->subscription->medicalInsurance->category ? [
                'id'   => $this->subscription->medicalInsurance->category->id,
                'name' => $this->subscription->medicalInsurance->category->name,
            ] : null,
            'created_at'           => $this->subscription->created_at?->toDateTimeString(),
            'updated_at'           => $this->subscription->updated_at?->toDateTimeString(),
        ];
    }
}
