<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoLanguage\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoLanguage\DTO\CreateEcoLanguageDTO;
use Modules\Ecommerce\EcoLanguage\Models\EcoLanguage;
use Modules\Ecommerce\EcoLanguage\Repositories\EcoLanguageRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class EcoLanguageCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoLanguageRepository $repository,
    ) {
    }

    public function create(CreateEcoLanguageDTO $createEcoLanguageDTO): Collection
    {
        $companyId = $createEcoLanguageDTO->getCompanyId();
        $languages = $createEcoLanguageDTO->getLanguages();

        // Start transaction to ensure data consistency
        return \DB::transaction(function () use ($companyId, $languages) {
            $createdLanguages = collect();

            // Handle default language logic
            $hasDefault = collect($languages)->contains('is_default', true);
            if ($hasDefault) {
                $this->repository->resetDefaultLanguages($companyId);
            }

            foreach ($languages as $languageData) {
                $languageId = $languageData['language_id'];
                $isDefault = $languageData['is_default'] ?? false;
                $isActive = $languageData['is_active'] ?? true;

                // Check if language already exists for this company
                $existingLanguage = $this->repository->findByCompanyAndLanguage($companyId, $languageId);

                $data = [
                    'company_id' => $companyId->toString(),
                    'language_id' => $languageId,
                    'is_default' => $isDefault,
                    'is_active' => $isActive,
                ];

                if ($existingLanguage) {
                    // Update existing language
                    $this->repository->updateEcoLanguage($companyId, $languageId, $data);
                    $createdLanguages->push($this->repository->findByCompanyAndLanguage($companyId, $languageId));
                } else {
                    // Create new language
                    $createdLanguages->push($this->repository->createEcoLanguage($data));
                }
            }

            return $createdLanguages;
        });
    }

    public function upsert(CreateEcoLanguageDTO $createEcoLanguageDTO): Collection
    {
        return $this->create($createEcoLanguageDTO);
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $companyId): Collection
    {
        return $this->repository->getCompanyLanguages($companyId);
    }
}
