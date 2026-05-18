<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Services;

use Modules\MedicalInsurance\DTO\CreateMedicalInsuranceSubscriptionDTO;
use Modules\MedicalInsurance\Models\MedicalInsuranceSubscription;
use Modules\MedicalInsurance\Repositories\MedicalInsuranceSubscriptionRepository;
use Ramsey\Uuid\UuidInterface;

class MedicalInsuranceSubscriptionCRUDService
{
    public function __construct(
        private MedicalInsuranceSubscriptionRepository $repository,
    ) {
    }

    public function list(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        return $this->repository->listSubscriptions($page, $perPage, $filters);
    }

    public function get(UuidInterface $id): MedicalInsuranceSubscription
    {
        return $this->repository->getSubscription($id);
    }

    public function create(CreateMedicalInsuranceSubscriptionDTO $dto): MedicalInsuranceSubscription
    {
        $familyMembersData = array_map(
            fn ($member) => $member->toArray(),
            $dto->familyMembers
        );

        return $this->repository->createWithFamilyMembers($dto->toArray(), $familyMembersData);
    }

    public function update(UuidInterface $id, array $data, array $familyMembers): bool
    {
        return $this->repository->updateSubscription($id, $data);
    }

    public function delete(UuidInterface $id): bool
    {
        return $this->repository->deleteSubscription($id);
    }
}
