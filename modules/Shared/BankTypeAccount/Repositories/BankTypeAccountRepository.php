<?php

declare(strict_types=1);

namespace Modules\Shared\BankTypeAccount\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\BankTypeAccount\Models\BankTypeAccount;

/**
 * @property BankTypeAccount $model
 * @method BankTypeAccount findOneOrFail($id)
 * @method BankTypeAccount findOneByOrFail(array $data)
 */
class BankTypeAccountRepository extends BaseRepository
{
    public function __construct(BankTypeAccount $model)
    {
        parent::__construct($model);
    }

    public function getBankTypeAccountList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getBankTypeAccount(UuidInterface $id): BankTypeAccount
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createBankTypeAccount(array $data): BankTypeAccount
    {
        return $this->create($data);
    }

    public function updateBankTypeAccount(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteBankTypeAccount(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
