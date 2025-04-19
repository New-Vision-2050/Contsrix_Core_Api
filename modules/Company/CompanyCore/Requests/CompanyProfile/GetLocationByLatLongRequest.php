<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\DTO\CompanyProfile\GeoCodingDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Ramsey\Uuid\Uuid;

class GetLocationByLatLongRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'lat' => 'required||between:-90,90',
            'long' => 'required|between:-180,180',

        ];
    }

    public function createGeoCodingDTO(): GeoCodingDTO
    {
        return new GeoCodingDTO(
            latitude: (string)$this->get('lat'),
            longitude: (string)$this->get('long'),

        );
    }
}

