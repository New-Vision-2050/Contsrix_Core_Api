<?php

declare(strict_types=1);

namespace Modules\Country\Services;

use Illuminate\Support\Collection;
use Modules\Country\DTO\CreateCountryDTO;
use Modules\Country\Models\Country;
use Modules\Country\Repositories\CountryRepository;
use Ramsey\Uuid\UuidInterface;

class CountryService
{
    public function timeZone($list, $countryId): array
    {
        $selectedCountryTimezones = [];
        $allTimezones = [];

        foreach ($list['data'] as $country) {
            $tzList = $country->timezones ?? [];
            foreach ($tzList as &$tz) {
                $tz['id'] = $country->id; // Attach country id to each timezone
            }

            if ($country->id == $countryId) {
                $selectedCountryTimezones = $tzList;
            } else {
                $allTimezones = array_merge($allTimezones, $tzList);
            }
        }

        return array_merge($selectedCountryTimezones, $allTimezones);
    }

}
