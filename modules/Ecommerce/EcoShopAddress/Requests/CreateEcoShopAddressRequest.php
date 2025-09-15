<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoShopAddress\DTO\CreateEcoShopAddressDTO;

class CreateEcoShopAddressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'required|exists:cities,id',
            'time_zone_id' => 'required|exists:time_zones,id',
    
            'district' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
    
            'building_number' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
    
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'country.string' => 'الدولة يجب أن تكون نص صحيح.',
            'country.max' => 'الدولة يجب ألا تتجاوز 100 حرف.',
            'city.string' => 'المدينة يجب أن تكون نص صحيح.',
            'city.max' => 'المدينة يجب ألا تتجاوز 100 حرف.',
            'district.string' => 'الحي يجب أن يكون نص صحيح.',
            'district.max' => 'الحي يجب ألا يتجاوز 100 حرف.',
            'street.string' => 'الشارع يجب أن يكون نص صحيح.',
            'street.max' => 'الشارع يجب ألا يتجاوز 255 حرف.',
            'building_number.string' => 'رقم المبنى يجب أن يكون نص صحيح.',
            'building_number.max' => 'رقم المبنى يجب ألا يتجاوز 50 حرف.',
            'postal_code.string' => 'الرمز البريدي يجب أن يكون نص صحيح.',
            'postal_code.max' => 'الرمز البريدي يجب ألا يتجاوز 20 حرف.',
            'additional_number.string' => 'الرقم الإضافي يجب أن يكون نص صحيح.',
            'additional_number.max' => 'الرقم الإضافي يجب ألا يتجاوز 50 حرف.',
            'latitude.numeric' => 'خط العرض يجب أن يكون رقم صحيح.',
            'latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90.',
            'longitude.numeric' => 'خط الطول يجب أن يكون رقم صحيح.',
            'longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180.',
            'full_address.string' => 'العنوان الكامل يجب أن يكون نص صحيح.',
            'full_address.max' => 'العنوان الكامل يجب ألا يتجاوز 1000 حرف.',
            'address_notes.string' => 'ملاحظات العنوان يجب أن تكون نص صحيح.',
            'address_notes.max' => 'ملاحظات العنوان يجب ألا تتجاوز 500 حرف.',
        ];
    }

    public function createCreateEcoShopAddressDTO(): CreateEcoShopAddressDTO
    {
        return new CreateEcoShopAddressDTO(
            companyId: Uuid::fromString(tenant("id")),
            countryId: (string) $this->get('country_id'),
            cityId: (string) $this->get('city_id'),
            timeZoneId: (string) $this->get('time_zone_id'),
            district: $this->get('district'),
            street: $this->get('street'),
            buildingNumber: $this->get('building_number'),
            postalCode: $this->get('postal_code'),
            latitude: $this->get('latitude') ? (float) $this->get('latitude') : null,
            longitude: $this->get('longitude') ? (float) $this->get('longitude') : null,
        );
    }
}
