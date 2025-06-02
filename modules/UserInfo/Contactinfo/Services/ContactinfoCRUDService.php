<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Services;

use Illuminate\Support\Collection;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\UserInfo\Contactinfo\Commands\UpdateContactinfoCommand;
use Modules\UserInfo\Contactinfo\DTO\CreateContactinfoDTO;
use Modules\UserInfo\Contactinfo\Models\ContactInfo;
use Modules\UserInfo\Contactinfo\Repositories\ContactinfoRepository;
use Ramsey\Uuid\UuidInterface;

class ContactinfoCRUDService
{
    public function __construct(
        private ContactinfoRepository $repository,
    ) {
    }

    public function create(UpdateContactinfoCommand $updateContactinfoCommand): ContactInfo
    {
         return $this->repository->createOrUpdateContactInfo($updateContactinfoCommand->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $companyId,UuidInterface $globalId): ?ContactInfo
    {
        return $this->repository->getContactinfo($companyId, $globalId);
    }
}
