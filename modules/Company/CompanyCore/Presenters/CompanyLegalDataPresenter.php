<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Presenters;

use Modules\Company\CompanyCore\Models\Company;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\CompanyCore\Models\CompanyLegalData;
use Modules\Shared\Media\Presenters\MediaPresenter;

class CompanyLegalDataPresenter extends AbstractPresenter
{
    private CompanyLegalData $company;

    public function __construct(CompanyLegalData $company)
    {
        $this->company = $company;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->company->id,
            'registration_number' => $this->company->registration_number,
            'registration_type' => $this->company->registrationType->name,
            'registration_type_id' => $this->company->registration_type_id,
            "start_date"=>$this->company->start_date,
            "end_date"=>$this->company->end_date,
            'file' => MediaPresenter::collection($this->company->getMedia('upload')),
        ];
    }
}
