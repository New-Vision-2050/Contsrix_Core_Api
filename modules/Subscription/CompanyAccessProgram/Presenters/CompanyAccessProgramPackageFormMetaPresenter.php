<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Presenters;

use Modules\Subscription\Enums\PeriodUnitEnum;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;

class CompanyAccessProgramPackageFormMetaPresenter extends AbstractPresenter
{
    private CompanyAccessProgram $companyAccessProgram;

    public function __construct(CompanyAccessProgram $companyAccessProgram)
    {
        $this->companyAccessProgram = $companyAccessProgram;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'period_units' => array_map(fn(PeriodUnitEnum $enum) => [
                'value' => $enum->value,
                'label' => $enum->label(),
            ], PeriodUnitEnum::cases()),
            'company_fields' => $this->companyAccessProgram->companyFields->map(fn($field) => [
                'id' => $field->id,
                'name' => $field->name,
            ]),
             'company_types' => $this->companyAccessProgram->companyTypes->map(fn($type) => [
                'id' => $type->id,
                'name' => $type->name,
            ]),
            'countries' => $this->companyAccessProgram->countries->map(fn($field) => [
                'id' => $field->id,
                'name' => $field->name,
                'currency' => $field->currency,
                'currency_name' => $field->currency_name,
                'currency_symbol' => $field->currency_symbol,
            ]),
        ];
    }
}
