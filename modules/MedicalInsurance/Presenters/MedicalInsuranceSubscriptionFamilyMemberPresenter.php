<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\MedicalInsurance\Models\MedicalInsuranceSubscriptionFamilyMember;

class MedicalInsuranceSubscriptionFamilyMemberPresenter extends AbstractPresenter
{
    private MedicalInsuranceSubscriptionFamilyMember $member;

    public function __construct(MedicalInsuranceSubscriptionFamilyMember $member)
    {
        $this->member = $member;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id'              => $this->member->id,
            'name'            => $this->member->name,
            'national_id'     => $this->member->national_id,
            'relation'        => $this->member->relation,
            'amount'          => $this->member->amount,
            'subscription_no' => $this->member->subscription_no,
            'created_at'      => $this->member->created_at?->toDateTimeString(),
            'updated_at'      => $this->member->updated_at?->toDateTimeString(),
        ];
    }
}
