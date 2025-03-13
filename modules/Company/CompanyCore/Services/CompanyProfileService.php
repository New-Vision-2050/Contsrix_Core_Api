<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Modules\AdminRequest\Repositories\AdminRequestRepository;
use Modules\Company\CompanyCore\DTO\CompanyProfile\GeoCodingDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use Modules\Company\CompanyCore\DTO\CreateCompanyDTO;
use Modules\Company\CompanyCore\Jobs\CheckCompanyActivity;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class CompanyProfileService
{
    public function __construct(
        private AdminRequestRepository $adminRequestRepository,
    ) {
    }

    public function updateCompanyProfileRequest(UpdateOfficialCompanyDataRequestDTO $companyDataRequestDTO)
    {
        $adminRequest = $this->adminRequestRepository->createAdminRequestForCompanyOfficialData(
            userId: auth()->user()->id,
            data: ["id"=>$companyDataRequestDTO->getId(),"data"=>$companyDataRequestDTO->toArray()],
            requestType: "companyOfficialDataUpdate",
            action:["ar"=>"طلب تعديل البيانات الرسميه للشركة","en"=>"Company official data update request"] ,
            notes: $companyDataRequestDTO->getNotes()

        );

        return $adminRequest;

    }

    public function geoCoding(GeoCodingDTO $geoCodingDTO)
    {
        $response = Http::get("https://maps.googleapis.com/maps/apis/geocode/json?latlng={$geoCodingDTO->getLatitude()},{$geoCodingDTO->getLongitude()}&language=ar&key=".env('GOOGLE_MAPS_API_KEY'));
        $poltical = "";
        $locality = "";
        $area = "";
        $country = "";
        foreach ($response['results'] as $key => $value) {
            if ($value['types'][0] == "political") {
                $poltical = $value["address_components"][0]["long_name"];
            }
            if ($value['types'][0] == "locality") {
                $locality = $value["address_components"][0]["long_name"];
            }

            if ($value['types'][0] == "administrative_area_level_1") {
                $area = $value["address_components"][0]["long_name"];
            }

            if ($value['types'][0] == "country") {
                $country = $value["address_components"][0]["long_name"];
            }
        }

//        return $country . " " . $area . " " . $locality . " " . $poltical;

        return [
            "country" => $country,
            "state" => $area,
            "city" => $locality,
            "neighborhood" => $poltical
        ];
    }

}
