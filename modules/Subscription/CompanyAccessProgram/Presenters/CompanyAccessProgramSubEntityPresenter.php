<?php declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Presenters;

use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgramProgram;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgramSubEntity;

class CompanyAccessProgramSubEntityPresenter extends AbstractPresenter
{
    private CompanyAccessProgramSubEntity $companyAccessProgram;

    public function __construct(CompanyAccessProgramSubEntity $companyAccessProgram)
    {
        $this->companyAccessProgram = $companyAccessProgram;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->companyAccessProgram->sub_entity_id,
            'name' => __("names".$this->companyAccessProgram->sub_entity_id),
            'slug' => $this->companyAccessProgram->sub_entity_id,
            "is_active"=>1,
        ];


        return $data;
    }



}
