<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Handlers;

use Illuminate\Support\Facades\DB;
use Modules\MedicalInsurance\Commands\UpdateMedicalInsuranceSubscriptionCommand;
use Modules\MedicalInsurance\Repositories\MedicalInsuranceSubscriptionRepository;

class UpdateMedicalInsuranceSubscriptionHandler
{
    public function __construct(
        private MedicalInsuranceSubscriptionRepository $repository,
    ) {
    }

    public function handle(UpdateMedicalInsuranceSubscriptionCommand $command): void
    {
        DB::transaction(function () use ($command) {
            $this->repository->updateSubscription($command->getId(), $command->toArray());
            $this->repository->replaceFamilyMembers($command->getId(), $command->getFamilyMembers());
        });
    }
}
