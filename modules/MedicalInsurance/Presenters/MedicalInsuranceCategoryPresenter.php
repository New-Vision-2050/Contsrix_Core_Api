<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Presenters;

use Modules\MedicalInsurance\Models\MedicalInsuranceCategory;
use BasePackage\Shared\Presenters\AbstractPresenter;

class MedicalInsuranceCategoryPresenter extends AbstractPresenter
{
    private MedicalInsuranceCategory $category;

    public function __construct(MedicalInsuranceCategory $category)
    {
        $this->category = $category;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id'                   => $this->category->id,
            'medical_insurance_id' => $this->category->medical_insurance_id,
            'name'                 => $this->category->name,
            'type'                 => $this->category->type,
            'coverage_limit'       => $this->category->coverage_limit,
            'description'          => $this->category->description,
            'created_at'           => $this->category->created_at?->toDateTimeString(),
            'updated_at'           => $this->category->updated_at?->toDateTimeString(),
        ];
    }
}
