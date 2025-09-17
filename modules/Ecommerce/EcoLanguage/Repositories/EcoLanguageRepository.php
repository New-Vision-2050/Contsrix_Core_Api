<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoLanguage\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoLanguage\Models\EcoLanguage;
use App\Traits\HasExport;

/**
 * @property EcoLanguage $model
 * @method EcoLanguage findOneOrFail($id)
 * @method EcoLanguage findOneByOrFail(array $data)
 */
class EcoLanguageRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoLanguage $model)
    {
        parent::__construct($model);
    }

    public function getEcoLanguageList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getCompanyLanguages(UuidInterface $companyId): Collection
    {
        return $this->model->forCompany($companyId->toString())->with('language')->get();
    }

    public function findByCompanyAndLanguage(UuidInterface $companyId, string $languageId): ?EcoLanguage
    {
        return $this->findOneBy([
            'company_id' => $companyId->toString(),
            'language_id' => $languageId,
        ]);
    }

    public function createEcoLanguage(array $data): EcoLanguage
    {
        return $this->create($data);
    }

    public function updateEcoLanguage(UuidInterface $companyId, string $languageId, array $data)
    {
        return $this->updateWhere([
            'company_id' => $companyId->toString(),
            'language_id' => $languageId,
        ], $data);
    }

    public function deleteCompanyLanguages(UuidInterface $companyId): bool
    {
        return $this->model->forCompany($companyId->toString())->delete();
    }

    public function resetDefaultLanguages(UuidInterface $companyId): bool
    {
        $this->updateWhere([
            'company_id' => $companyId->toString(),
        ], ['is_default' => 0]);
        
        return true;
    }

    public function deleteEcoLanguage(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
