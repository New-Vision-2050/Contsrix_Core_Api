<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\Contactinfo\Models\ContactInfo;
use Ramsey\Uuid\UuidInterface;
/**
 * @property ContactInfo $model
 * @method Contactinfo findOneOrFail($id)
 * @method Contactinfo findOneByOrFail(array $data)
 */
class ContactinfoRepository extends BaseRepository
{
    public function __construct(
        ContactInfo $model,
        private UserRepository $userRepository,
        )
    {
        parent::__construct($model);
    }

    public function getContactinfoList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getContactinfo(UuidInterface $companyId, UuidInterface $globalId): ?ContactInfo
    {
        return $this->model->where([
            'global_id' => $globalId,
            'company_id' => $companyId,
        ])->first();
    }

    public function createContactinfo(array $data): ContactInfo
    {
        return $this->create($data);
    }

    public function createOrUpdateContactInfo(array $data): ContactInfo
    {
        $contactInfo = $this->model->where([
            'global_id' => $data['global_id'],
            'company_id' => $data['company_id'],
        ])->first();

        if ($contactInfo) {
            $contactInfo->update($data);
            return $contactInfo;
        }

        return $this->model->create($data);
    }


    public function updateContactinfo(UuidInterface $id, array $data, UuidInterface $userId = null)
    {
        if (isset($data['phone'])) {
            $this->userRepository->update($userId, $data);
        }
        return $this->update($id, $data);
    }

    public function deleteContactinfo(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
