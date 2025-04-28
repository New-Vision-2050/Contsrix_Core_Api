<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\UuidInterface;

/**
 * @property CompanyUser $model
 * @method Contactinfo findOneOrFail($id)
 * @method Contactinfo findOneByOrFail(array $data)
 */
class ContactinfoRepository extends BaseRepository
{
    public function __construct(CompanyUser $model, private UserRepository $userRepository)
    {
        parent::__construct($model);
    }

    public function getContactinfoList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getContactinfo(UuidInterface $id): CompanyUser
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createContactinfo(array $data): CompanyUser
    {
        return $this->create($data);
    }

    public function updateContactinfo(UuidInterface $id, array $data): bool
    {
        $company = $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
        $this->update($id, $data);

       return $users = $this->userRepository->updateWhere(
            ["global_company_user_id" => $company->global_id],
            ['phone' => $data['phone'] ?? null]
        );
    }

    public function deleteContactinfo(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
