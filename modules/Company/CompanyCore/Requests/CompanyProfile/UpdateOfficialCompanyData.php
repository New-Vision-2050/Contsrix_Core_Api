<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateOfficialCompanyDataCommand;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyCore\Commands\UpdateCompanyCommand;
use App\Rules\Company\CompanyCore\Rules\RegistrationNoRule;
class UpdateOfficialCompanyData extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_en' => 'required|string',
            'email' => 'required|email|string',
            'phone' => 'required|string',
        ];
    }

    public function createUpdateOfficialCompanyDataCommand(): UpdateOfficialCompanyDataCommand
    {
        return new UpdateOfficialCompanyDataCommand(
            id: Uuid::fromString($this->route('id')),
            nameEn: $this->get('name'),
            email: $this->get('email'),
            phone: $this->get('phone'),
        );
    }
}

