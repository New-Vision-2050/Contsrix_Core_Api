<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Handlers;

use Illuminate\Support\Facades\DB;
use Modules\MedicalInsurance\Commands\BulkReplaceMedicalInsuranceSubscriptionsCommand;
use Modules\MedicalInsurance\Models\MedicalInsuranceSubscription;
use Modules\MedicalInsurance\Repositories\MedicalInsuranceSubscriptionRepository;

class UpdateMedicalInsuranceSubscriptionHandler
{
    public function __construct(
        private MedicalInsuranceSubscriptionRepository $repository,
    ) {
    }

    /**
     * @return array<MedicalInsuranceSubscription>
     */
    public function handle(BulkReplaceMedicalInsuranceSubscriptionsCommand $command): array
    {
        return DB::transaction(function () use ($command) {
            $dtos = $command->getDtos();

            $pairs = collect($dtos)
                ->map(fn ($dto) => $dto->userId . '|' . $dto->medicalInsuranceId)
                ->unique()
                ->values();

            foreach ($pairs as $pair) {
                [$userId, $insuranceId] = explode('|', $pair);
                $this->repository->deleteByUserAndInsurance($userId, $insuranceId);
            }

            return array_map(
                fn ($dto) => $this->repository->createWithFamilyMembers(
                    $dto->toArray(),
                    array_map(fn ($m) => $m->toArray(), $dto->familyMembers)
                ),
                $dtos
            );
        });
    }
}
