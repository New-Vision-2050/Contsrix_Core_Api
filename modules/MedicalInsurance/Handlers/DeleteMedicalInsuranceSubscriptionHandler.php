<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Handlers;

use Modules\MedicalInsurance\Repositories\MedicalInsuranceSubscriptionFamilyMemberRepository;
use Modules\MedicalInsurance\Repositories\MedicalInsuranceSubscriptionRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteMedicalInsuranceSubscriptionHandler
{
    public function __construct(
        private MedicalInsuranceSubscriptionRepository $repository,
        private MedicalInsuranceSubscriptionFamilyMemberRepository $familyMemberRepository,
    ) {
    }

    public function handle(UuidInterface $id): void
    {
        $this->familyMemberRepository->deleteBySubscription($id);
        $this->repository->deleteSubscription($id);
    }
}
