<?php

declare(strict_types=1);

namespace Modules\Stakeholder\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Stakeholder\Models\Stakeholder;
use Ramsey\Uuid\UuidInterface;

class StakeholderRepository extends BaseRepository
{
    public function __construct(Stakeholder $model)
    {
        parent::__construct($model);
    }

    public function getStakeholderList(?int $page = null, ?int $perPage = 10): array
    {
        return $this->paginated(
            [],
            page: $page,
            perPage: $perPage,
        );
    }

    public function getStakeholder(UuidInterface $id): Stakeholder
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createStakeholder(array $data): Stakeholder
    {
        return $this->create($data);
    }

    public function updateStakeholder(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteStakeholder(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
