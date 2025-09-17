<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoBankAccount\Models\EcoBankAccount;
use App\Traits\HasExport;

/**
 * @property EcoBankAccount $model
 * @method EcoBankAccount findOneOrFail($id)
 * @method EcoBankAccount findOneByOrFail(array $data)
 */
class EcoBankAccountRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoBankAccount $model)
    {
        parent::__construct($model);
    }

    public function getEcoBankAccountList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoBankAccount(UuidInterface $id): EcoBankAccount
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoBankAccount(array $data): EcoBankAccount
    {
        return $this->create($data);
    }

    public function updateEcoBankAccount(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function resetPrimaryAccounts(UuidInterface $companyId): bool
    {
        $this->updateWhere([
            'company_id' => $companyId->toString(),
        ], ['is_primary' => false]);
        
        return true;
    }

    public function findByCompanyAndAccountNumber(UuidInterface $companyId, string $accountNumber): ?EcoBankAccount
    {
        return $this->model->where('company_id', $companyId->toString())
                          ->where('account_number', $accountNumber)
                          ->first();
    }

    public function getCompanyBankAccounts(UuidInterface $companyId): Collection
    {
        return $this->model->forCompany($companyId->toString())->get();
    }

    public function deleteEcoBankAccount(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
