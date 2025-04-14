<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\BankAccount\Models\BankAccount;

/**
 * @property BankAccount $model
 * @method BankAccount findOneOrFail($id)
 * @method BankAccount findOneByOrFail(array $data)
 */
class BankAccountRepository extends BaseRepository
{
    public function __construct(BankAccount $model)
    {
        parent::__construct($model);
    }

    public function getBankAccountList(UuidInterface $companyId, UuidInterface $globalId, ?int $page, ?int $perPage = 10):array
    {
        //, 'global_id' => $globalId
        return $this->paginated(
            ['company_id' => $companyId],
            $page,
            $perPage
        );
    }
    public function getBankAccount(UuidInterface $id): BankAccount
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createBankAccount(array $data): BankAccount
    {
        return $this->create($data);
    }

    public function updateBankAccount(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteBankAccount(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
