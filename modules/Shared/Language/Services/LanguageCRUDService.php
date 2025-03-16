<?php

declare(strict_types=1);

namespace Modules\Shared\Language\Services;

use Illuminate\Support\Collection;
use Modules\Shared\Language\DTO\CreateLanguageDTO;
use Modules\Shared\Language\Models\Language;
use Modules\Shared\Language\Repositories\LanguageRepository;
use Ramsey\Uuid\UuidInterface;

class LanguageCRUDService
{
    public function __construct(
        private LanguageRepository $repository,
    ) {
    }

    public function create(CreateLanguageDTO $createLanguageDTO): Language
    {
         return $this->repository->createLanguage($createLanguageDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Language
    {
        return $this->repository->getLanguage(
            id: $id,
        );
    }
}
