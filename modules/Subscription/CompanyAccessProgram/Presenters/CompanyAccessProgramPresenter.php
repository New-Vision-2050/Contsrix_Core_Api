<?php declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Presenters;

use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use Modules\Subscription\CompanyAccessProgram\Services\CompanyAccessProgramCRUDService;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Ramsey\Uuid\Uuid;

class CompanyAccessProgramPresenter extends AbstractPresenter
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
            'status' => $this->companyAccessProgram->is_active ? true : false,
            "company_fields"=>$this->companyAccessProgram->companyFields,
            "company_types"=>$this->companyAccessProgram->companyTypes,
            "countries"=>$this->companyAccessProgram->countries
//            "programs"=>$this->companyAccessProgram->programs,
//            "sub_entities"=>$this->companyAccessProgram->subEntities
        ];

        if (0) {
            try {
                // Get hierarchical structure with nested sub_entities
                $data['programs'] = $this->service->getProgramsHierarchy($this->companyAccessProgram->id);

                // Remove separate sub_entities array when using hierarchical structure
                unset($data['sub_entities']);
            } catch (\Exception $e) {
                // Fallback to old structure if hierarchy fails
                $data['programs'] = CompanyAccessProgramProgramsPresenter::collection($this->companyAccessProgram->programs);
                $data['sub_entities'] = CompanyAccessProgramSubEntityPresenter::collection($this->companyAccessProgram->subEntities);
            }
        } else {
            // Use old structure for individual items or when no service
            $data['programs'] = CompanyAccessProgramProgramsPresenter::collection($this->companyAccessProgram->programs);
            $data['sub_entities'] = CompanyAccessProgramSubEntityPresenter::collection($this->companyAccessProgram->subEntities);
        }

        if ($isListing) {
            $data['programs_count'] = $this->calculateProgramsCount($this->companyAccessProgram);
            $data['company_fields_count'] = $this->companyAccessProgram->company_fields_count ?? 0;
            $data['packages_count'] = $this->companyAccessProgram->packages_count ?? 0;
        }

        return $data;

        // Use hierarchical structure always when service is provided
//        if ($this->service) {
//            try {
//                // Get hierarchical structure with nested sub_entities
//                $data['programs'] = $this->service->getProgramsHierarchy($this->companyAccessProgram->id);
//
//                // Remove separate sub_entities array when using hierarchical structure
//                unset($data['sub_entities']);
//            } catch (\Exception $e) {
//                // Fallback to old structure if hierarchy fails
//                $data['programs'] = CompanyAccessProgramProgramsPresenter::collection($this->companyAccessProgram->programs);
//                $data['sub_entities'] = CompanyAccessProgramSubEntityPresenter::collection($this->companyAccessProgram->subEntities);
//            }
//        } else {
//            // Use old structure for individual items or when no service
//            $data['programs'] = CompanyAccessProgramProgramsPresenter::collection($this->companyAccessProgram->programs);
//            $data['sub_entities'] = CompanyAccessProgramSubEntityPresenter::collection($this->companyAccessProgram->subEntities);
//        }
//
//        if ($isListing) {
//            $data['programs_count'] = $this->calculateProgramsCount($this->companyAccessProgram);
//            $data['company_fields_count'] = $this->companyAccessProgram->company_fields_count ?? 0;
//            $data['packages_count'] = $this->companyAccessProgram->packages_count ?? 0;
//        }
//        return $data;
    }

    private function calculateProgramsCount(CompanyAccessProgram $companyAccessProgram): int
    {
        $programsCount = $companyAccessProgram->programs_count ?? 0;
        $subEntitiesCount = $companyAccessProgram->sub_entities_count ?? 0;

        return $programsCount + $subEntitiesCount;
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
