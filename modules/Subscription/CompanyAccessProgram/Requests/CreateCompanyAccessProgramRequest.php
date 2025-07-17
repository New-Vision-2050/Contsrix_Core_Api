<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Subscription\CompanyAccessProgram\DTO\CreateCompanyAccessProgramDTO;
use Modules\Subscription\CompanyAccessProgram\DTO\ProgramPayloadDTO;
use Modules\Subscription\CompanyAccessProgram\Rules\ValidProgramStructure;

class CreateCompanyAccessProgramRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:company_access_programs,name',
            'programs' => ['nullable', 'array'],
            'company_fields' => 'nullable|array',
            'company_fields.*' => ['uuid', Rule::exists('company_fields', 'id')],
            'company_types' => 'nullable|array',
            'company_types.*' => ['uuid', Rule::exists('company_types', 'id')],
            'countries' => 'nullable|array',
            'countries.*' => ['integer', Rule::exists('countries', 'id')],
        ];
    }


    public function createCreateCompanyAccessProgramDTO(): CreateCompanyAccessProgramDTO
    {
        $programs = array_map(
            fn ($program) => ProgramPayloadDTO::fromArray($program),
            $this->get('programs', [])
        );

        return new CreateCompanyAccessProgramDTO(
            name: $this->get('name'),
            rawPrograms: $programs,
            companyFields: $this->get('company_fields', []),
            companyTypes: $this->get('company_types', []),
            countries: $this->get('countries', []),
        );
    }

}
