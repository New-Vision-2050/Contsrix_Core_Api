<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoCurrency\DTO\CreateEcoCurrencyDTO;
use Modules\Ecommerce\EcoCurrency\DTO\UpsertEcoCurrencyDTO;
use Modules\Ecommerce\EcoCurrency\Models\EcoCurrency;
use Modules\Ecommerce\EcoCurrency\Repositories\EcoCurrencyRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class EcoCurrencyCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoCurrencyRepository $repository,
    ) {
    }

    public function create(CreateEcoCurrencyDTO $createEcoCurrencyDTO): EcoCurrency
    {
         return $this->repository->createEcoCurrency($createEcoCurrencyDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoCurrency
    {
        return $this->repository->getEcoCurrency(
            id: $id,
        );
    }

    public function upsert(UpsertEcoCurrencyDTO $upsertEcoCurrencyDTO): Collection
    {
        $companyId = $upsertEcoCurrencyDTO->getCompanyId();
        $currencies = $upsertEcoCurrencyDTO->getCurrencies();

        // Start transaction to ensure data consistency
        return \DB::transaction(function () use ($companyId, $currencies) {
            $createdCurrencies = collect();

            // Handle default currency logic
            $hasDefault = collect($currencies)->contains('is_default', true);
            if ($hasDefault) {
                $this->repository->resetDefaultCurrencies($companyId);
            }

            foreach ($currencies as $currencyData) {
                $currencyId = $currencyData['currency_id'];
                $isDefault = $currencyData['is_default'] ?? false;
                $isActive = $currencyData['is_active'] ?? true;

                // Check if currency already exists for this company
                $existingCurrency = $this->repository->findByCompanyAndCurrency($companyId, $currencyId);

                $data = [
                    'company_id' => $companyId->toString(),
                    'currency_id' => $currencyId,
                    'is_default' => $isDefault,
                    'is_active' => $isActive,
                ];

                if ($existingCurrency) {
                    // Update existing currency
                    $this->repository->updateEcoCurrency($existingCurrency->id, $data);
                    $createdCurrencies->push($existingCurrency->fresh());
                } else {
                    // Create new currency
                    $createdCurrencies->push($this->repository->createEcoCurrency($data));
                }
            }

            return $createdCurrencies;
        });
    }
}
