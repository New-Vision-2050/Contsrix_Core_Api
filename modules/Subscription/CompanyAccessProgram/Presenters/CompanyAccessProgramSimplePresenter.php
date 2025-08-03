<?php declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Presenters;

use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use Modules\Subscription\CompanyAccessProgram\Services\CompanyAccessProgramCRUDService;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Ramsey\Uuid\Uuid;

class CompanyAccessProgramSimplePresenter extends AbstractPresenter
{
    private CompanyAccessProgram $companyAccessProgram;
    private ?CompanyAccessProgramCRUDService $service;

    public function __construct(CompanyAccessProgram $companyAccessProgram, ?CompanyAccessProgramCRUDService $service = null)
    {
        $this->companyAccessProgram = $companyAccessProgram;
        $this->service = $service;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->companyAccessProgram->id,
            'name' => $this->companyAccessProgram->name,

//            "sub_entities"=>$this->companyAccessProgram->subEntities
        ];

        return $data;
    }

    private function calculateProgramsCount(CompanyAccessProgram $companyAccessProgram): int
    {
        $programsCount = $companyAccessProgram->programs_count ?? 0;
        $subEntitiesCount = $companyAccessProgram->sub_entities_count ?? 0;

        return  $subEntitiesCount;
    }

    public function getData(bool $isListing = false): ?array
    {
        $array = $this->present($isListing);
        foreach ($array as $key => $value) {
            if ($value === 'delete_this_row') {
                unset($array[$key]);
            }
            if ($value === 'delete_this_array') {
                return null;
            }
        }
        return $array;
    }

    /**
     * Create collection of presenters with service
     *
     * @param iterable $collection
     * @param mixed ...$additionalParams
     * @return array
     */
    public static function collection(iterable $collection, ...$additionalParams): array
    {
        $service = $additionalParams[0] ?? null;

        return collect($collection)->map(function ($item) use ($service) {
            $presenter = new self($item, $service);
            return $presenter->getData(true);
        })->toArray();
    }
}
