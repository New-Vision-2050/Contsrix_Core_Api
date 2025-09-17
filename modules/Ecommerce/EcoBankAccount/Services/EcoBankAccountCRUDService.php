<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\EcoBankAccount\DTO\CreateEcoBankAccountDTO;
use Modules\Ecommerce\EcoBankAccount\Models\EcoBankAccount;
use Modules\Ecommerce\EcoBankAccount\Repositories\EcoBankAccountRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class EcoBankAccountCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoBankAccountRepository $repository,
    ) {
    }

    public function create(CreateEcoBankAccountDTO $createEcoBankAccountDTO): EcoBankAccount
    {
        return DB::transaction(function () use ($createEcoBankAccountDTO) {
            $data = $createEcoBankAccountDTO->toArray();
            
            // If this is set as primary, reset other primary accounts
            if ($data['is_primary']) {
                $this->repository->resetPrimaryAccounts($createEcoBankAccountDTO->companyId);
            }
            
            return $this->repository->createEcoBankAccount($data);
        });
    }


    public function update(UuidInterface $id, array $data): EcoBankAccount
    {
        return DB::transaction(function () use ($id, $data) {
            $bankAccount = $this->repository->getEcoBankAccount($id);
            
            // If this is set as primary, reset other primary accounts
            if (isset($data['is_primary']) && $data['is_primary']) {
                $this->repository->resetPrimaryAccounts(\Ramsey\Uuid\Uuid::fromString($bankAccount->company_id));
            }
            
            $this->repository->updateEcoBankAccount($id, $data);
            
            return $this->repository->getEcoBankAccount($id);
        });
    }

    public function delete(UuidInterface $id): bool
    {
        return $this->repository->deleteEcoBankAccount($id);
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoBankAccount
    {
        return $this->repository->getEcoBankAccount(
            id: $id,
        );
    }
}
