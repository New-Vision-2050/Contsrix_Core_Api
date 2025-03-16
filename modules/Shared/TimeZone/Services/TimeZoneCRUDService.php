<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Services;

use Illuminate\Support\Collection;
use Modules\Shared\TimeZone\DTO\CreateTimeZoneDTO;
use Modules\Shared\TimeZone\Models\TimeZone;
use Modules\Shared\TimeZone\Repositories\TimeZoneRepository;
use Ramsey\Uuid\UuidInterface;

class TimeZoneCRUDService
{
    public function __construct(
        private TimeZoneRepository $repository,
    ) {
    }

    public function create(CreateTimeZoneDTO $createTimeZoneDTO): TimeZone
    {
         return $this->repository->createTimeZone($createTimeZoneDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): TimeZone
    {
        return $this->repository->getTimeZone(
            id: $id,
        );
    }
}
