<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Subscription\CompanyAccessProgram\DTO\ProgramPayloadDTO;
use Modules\Subscription\CompanyAccessProgram\Rules\ValidProgramStructure;
use Ramsey\Uuid\Uuid;
use Modules\Subscription\CompanyAccessProgram\Commands\UpdateCompanyAccessProgramCommand;
use Modules\Subscription\CompanyAccessProgram\Handlers\UpdateCompanyAccessProgramHandler;

class UpdateCompanyAccessProgramRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', Rule::unique('company_access_programs', 'name')->ignore($this->route('id'))],
            'programs' => ['required', 'array'],
            'company_fields' => 'nullable|array',
            'company_fields.*' => ['uuid', Rule::exists('company_fields', 'id')],
            'company_types' => 'nullable|array',
            'company_types.*' => ['uuid', Rule::exists('company_types', 'id')],
            'countries' => 'nullable|array',
            'countries.*' => ['integer', Rule::exists('countries', 'id')],
        ];
    }

    public function createUpdateCompanyAccessProgramCommand(): UpdateCompanyAccessProgramCommand
    {
        $programs = array_map(
            fn ($program) => ProgramPayloadDTO::fromArray($program),
            $this->get('programs', [])
        );

        return new UpdateCompanyAccessProgramCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            programs: $programs,
            companyFields: $this->get('company_fields', []),
            companyTypes: $this->get('company_types', []),
            countries: $this->get('countries', []),
        );
    }
}
