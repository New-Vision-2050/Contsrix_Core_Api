<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\DTO\CompanyProfile\AssignLogoToCompanyDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Ramsey\Uuid\Uuid;

class SetCompanyLogoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'logo' => 'required|image|mimes:jpeg,jpg,png,gif,svg|max:5000|dimensions:min_width=1920,min_height=1080'
            ];
    }

    public function createAssignLogoToCompanyDTO(): AssignLogoToCompanyDTO
    {
        return new AssignLogoToCompanyDTO(
            id: Uuid::fromString($this->route('id')),
            logo: $this->file("logo"),
        );
    }
}

