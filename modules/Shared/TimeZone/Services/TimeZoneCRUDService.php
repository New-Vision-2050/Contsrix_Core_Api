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
        $countryId = request('country_id');

        return $this->repository->paginated(
            [],
            $page,
            $perPage,
            'created_at',
            'desc',
            function ($query) use ($countryId) {
                // Ensure the country is active
                $query->whereHas('country', fn($q) => $q->where('status', 1));

                if ($countryId) {
                    $query->orderByRaw('country_id = ? DESC', [$countryId]);
                }
            }
        );
    }

    public function get(UuidInterface $id): TimeZone
    {
        return $this->repository->getTimeZone(
            id: $id,
        );
    }
}
