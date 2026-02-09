<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\DTO\CompanyProfile\CreateCompanyLegalDataDTO;
use Modules\Company\CompanyCore\Rules\ValidateRegistrationNumber;
use Modules\Company\CompanyCore\Rules\ValidateStartDateWithMinimumDays;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;

class CreateCompanyLegalDataRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function rules(): array
    {
        $rules = [
            'data' => 'required|array',
            'data.*.registration_type_id' => 'required|exists:company_registration_types,id',
            'data.*.regestration_number' => 'nullable|string',
            'data.*.start_date' => 'nullable|date|before_or_equal:data.*.end_date',
            'data.*.end_date' => 'nullable|date',
            'data.*.files' => 'nullable|array',
            'data.*.files.*' => 'nullable|file|mimes:pdf,jpeg,jpg,png,doc,docx',
        ];

        // Add custom validation rules for each data entry
        $data = $this->input('data', []);
        foreach ($data as $index => $item) {
            $rules["data.{$index}.regestration_number"][] = new ValidateRegistrationNumber($index);
            $rules["data.{$index}.start_date"][] = new ValidateStartDateWithMinimumDays($index);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'registration_type_id.exists' => __('validation.company_legal.registration_type_exists'),
            'start_date.date' => __('validation.company_legal.start_date_invalid'),
            'start_date.before_or_equal' => __('validation.company_legal.start_date_before_end'),
            'end_date.date' => __('validation.company_legal.end_date_invalid'),
            'end_date.after_or_equal' => __('validation.company_legal.end_date_after_start'),
            'file.*.mimes' => __('validation.company_legal.file_mimes'),
            'regestration_number.required' => __('validation.company_legal.regestration_number_required'),
        ];
    }

    public function createCreateCompanyLegalDataDTOs(): array
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();

        $dtos = [];
        $data = $this->data;

        foreach ($data as $index => $item) {
            $files = [];
            if (isset($item['files']) && is_array($item['files'])) {
                foreach ($item['files'] as $fileIndex => $file) {
                    if ($this->hasFile("data.{$index}.files.{$fileIndex}")) {
                        $files[] = $this->file("data.{$index}.files.{$fileIndex}");
                    }
                }
            }

            $dtos[] = new CreateCompanyLegalDataDTO(
                managementHierarchy: $branch,
                registrationTypeId: isset($item['registration_type_id']) ? Uuid::fromString($item['registration_type_id']) : null,
                registrationNumber: $item['regestration_number'] ?? null,
                startDate: isset($item['start_date']) ? Carbon::parse($item['start_date'])->format('Y-m-d') : null,
                endDate: isset($item['end_date']) ? Carbon::parse($item['end_date'])->format('Y-m-d') : null,
                files: !empty($files) ? $files : null,
            );
        }

        return $dtos;
    }
}
