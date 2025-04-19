<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\Services;

use Illuminate\Support\Collection;
use Modules\Shared\Currency\DTO\CreateCurrencyDTO;
use Modules\Shared\Currency\Models\Currency;
use Modules\Shared\Currency\Repositories\CurrencyRepository;
use Ramsey\Uuid\UuidInterface;

class CurrencyCRUDService
{
    public function __construct(
        private CurrencyRepository $repository,
    ) {
    }

    public function create(CreateCurrencyDTO $createCurrencyDTO): Currency
    {
         return $this->repository->createCurrency($createCurrencyDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Currency
    {
        return $this->repository->getCurrency(
            id: $id,
        );
    }
}
