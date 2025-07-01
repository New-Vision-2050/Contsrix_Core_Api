<?php declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Presenters;

use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CompanyAccessProgramPresenter extends AbstractPresenter
{
    private CompanyAccessProgram $companyAccessProgram;

    public function __construct(CompanyAccessProgram $companyAccessProgram)
    {
        $this->companyAccessProgram = $companyAccessProgram;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->companyAccessProgram->id,
            'name' => $this->companyAccessProgram->name,
            'status' => $this->companyAccessProgram->is_active ? true : false,
        ];

        if ($isListing) {
            $data['programs_count'] = $this->calculateProgramsCount($this->companyAccessProgram);
            $data['company_fields_count'] = $this->companyAccessProgram->company_fields_count ?? 0;
            $data['packages_count'] = $this->companyAccessProgram->packages_count ?? 0;
        }
        return $data;
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
}
