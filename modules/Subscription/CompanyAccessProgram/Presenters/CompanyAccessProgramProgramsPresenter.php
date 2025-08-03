<?php declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Presenters;

use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgramProgram;

class CompanyAccessProgramProgramsPresenter extends AbstractPresenter
{
    private CompanyAccessProgramProgram $companyAccessProgram;

    public function __construct(CompanyAccessProgramProgram $companyAccessProgram)
    {
        $this->companyAccessProgram = $companyAccessProgram;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->companyAccessProgram->program_id,
            'name' => __("names".$this->companyAccessProgram->program_id),
            'slug' => $this->companyAccessProgram->program_id,
            "is_active"=>1,


        ];


        return $data;
    }



}
