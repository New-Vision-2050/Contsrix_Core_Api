<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Warehous\DTO\CreateWarehousDTO;

class CreateWarehousRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['boolean'],
            'country_id' => ['required',  'exists:countries,id'],
            'city_id' => ['required',  'exists:cities,id'],
            'district' => ['nullable', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => __('warehous::validation.name_required'),
            'name.string' => __('warehous::validation.name_string'),
            'name.max' => __('warehous::validation.name_max'),
            'is_default.boolean' => __('warehous::validation.is_default_boolean'),
            'is_default.unique' => __('warehous::validation.is_default_unique'),
            'country_id.required' => __('warehous::validation.country_id_required'),
            'country_id.uuid' => __('warehous::validation.country_id_uuid'),
            'country_id.exists' => __('warehous::validation.country_id_exists'),
            'city_id.required' => __('warehous::validation.city_id_required'),
            'city_id.uuid' => __('warehous::validation.city_id_uuid'),
            'city_id.exists' => __('warehous::validation.city_id_exists'),
            'district.string' => __('warehous::validation.district_string'),
            'district.max' => __('warehous::validation.district_max'),
            'street.required' => __('warehous::validation.street_required'),
            'street.string' => __('warehous::validation.street_string'),
            'street.max' => __('warehous::validation.street_max'),
            'latitude.numeric' => __('warehous::validation.latitude_numeric'),
            'latitude.between' => __('warehous::validation.latitude_between'),
            'longitude.numeric' => __('warehous::validation.longitude_numeric'),
            'longitude.between' => __('warehous::validation.longitude_between'),
        ];
    }

    public function createCreateWarehousDTO(): CreateWarehousDTO
    {
        $validatedData = $this->validated();

        return new CreateWarehousDTO(
            companyId: Uuid::fromString(tenant("id")),
            name: $validatedData['name'],
            isDefault: $validatedData['is_default'] ?? false,
            countryId: (string) $validatedData['country_id'],
            cityId: (string)$validatedData['city_id'],
            district: $validatedData['district'] ?? null,
            street: $validatedData['street'],
            latitude: $validatedData['latitude'] ?? null,
            longitude: $validatedData['longitude'] ?? null,
        );
    }
}
