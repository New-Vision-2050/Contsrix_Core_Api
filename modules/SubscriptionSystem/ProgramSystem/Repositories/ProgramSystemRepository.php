<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\SubscriptionSystem\ProgramSystem\Models\ProgramSystem;

/**
 * @property ProgramSystem $model
 * @method ProgramSystem findOneOrFail($id)
 * @method ProgramSystem findOneByOrFail(array $data)
 */
class ProgramSystemRepository extends BaseRepository
{
    public function __construct(ProgramSystem $model)
    {
        parent::__construct($model);
    }

    public function getProgramSystemList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getProgramSystem(UuidInterface $id): ProgramSystem
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createProgramSystem(array $data): ProgramSystem
    {
        return $this->create($data);
    }

    public function updateProgramSystem(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProgramSystem(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function toggleIsActive(UuidInterface $id): ProgramSystem
    {
        $programSystem = $this->getProgramSystem($id);

        $programSystem->update([
            'is_active' => !$programSystem->is_active,
        ]);

        return $programSystem->refresh();
    }

}
