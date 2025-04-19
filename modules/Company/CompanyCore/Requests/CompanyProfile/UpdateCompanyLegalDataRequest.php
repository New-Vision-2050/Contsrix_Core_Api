<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateCompanyLegalDataCommand;
use Modules\Company\CompanyCore\DTO\CompanyProfile\RequestUpdateLegalCompanyDataRequestDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Ramsey\Uuid\Uuid;

class UpdateCompanyLegalDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [

            "start_date" => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            "file"=>"required|mimes:pdf,jpeg,jpg,png,doc,docx"
            ];
    }

    public function createUpdateLegalCompanyDataCommand(): UpdateCompanyLegalDataCommand
    {
        return new UpdateCompanyLegalDataCommand (
            id: Uuid::fromString($this->route("id")),
            startDate: $this->start_date,
            endDate: $this->end_date,
            file: $this->file("file"),
        );
    }
}

