<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateCompanyLegalDataCommand;
use Modules\Company\CompanyCore\DTO\CompanyProfile\RequestUpdateLegalCompanyDataRequestDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Ramsey\Uuid\Uuid;

class UpdateCompanyLegalDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "data" => 'nullable|array',
            "data.*.id" => 'required|exists:company_legal_data,id',
            "data.*.start_date" => 'nullable|date|before_or_equal:data.*.end_date',
            'data.*.end_date' => 'nullable|date|after_or_equal:data.*.start_date',

            'data.*.file' => 'nullable|array',
            'data.*.file.*' => 'nullable|file|mimes:pdf,jpeg,jpg,png,doc,docx',
            ];
    }

    public function createUpdateLegalCompanyDataCommand(): UpdateCompanyLegalDataCommand
    {
        $data = $this->data;
        return new UpdateCompanyLegalDataCommand (
            data: $data

        );
    }
}

