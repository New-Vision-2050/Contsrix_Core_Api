<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Services;

use Illuminate\Support\Collection;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\UserInfo\Contactinfo\DTO\CreateContactinfoDTO;
use Modules\UserInfo\Contactinfo\Models\Contactinfo;
use Modules\UserInfo\Contactinfo\Repositories\ContactinfoRepository;
use Ramsey\Uuid\UuidInterface;

class ContactinfoCRUDService
{
    public function __construct(
        private ContactinfoRepository $repository,
    ) {
    }

    public function create(CreateContactinfoDTO $createContactinfoDTO): CompanyUser
    {
         return $this->repository->createContactinfo($createContactinfoDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): CompanyUser
    {
        return $this->repository->getContactinfo(
            id: $id,
        );
    }
}
