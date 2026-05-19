<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\MedicalInsurance\Models\MedicalInsuranceSubscriptionFamilyMember;
use Ramsey\Uuid\UuidInterface;

/**
 * @property MedicalInsuranceSubscriptionFamilyMember $model
 */
class MedicalInsuranceSubscriptionFamilyMemberRepository extends BaseRepository
{
    public function __construct(MedicalInsuranceSubscriptionFamilyMember $model)
    {
        parent::__construct($model);
    }

    public function getFamilyMember(UuidInterface $id): MedicalInsuranceSubscriptionFamilyMember
    {
        return $this->model->where('id', $id->toString())->firstOrFail();
    }

    public function deleteBySubscription(UuidInterface $subscriptionId): int
    {
        return $this->model
            ->where('medical_insurance_subscription_id', $subscriptionId->toString())
            ->delete();
    }
}
