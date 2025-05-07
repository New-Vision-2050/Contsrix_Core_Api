<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use App\Http\Controllers\Api\V1\Admin\Tenant\TenantServiceClass;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Modules\AdminRequest\Repositories\AdminRequestRepository;
use Modules\Company\CompanyCore\DTO\CompanyProfile\AssignLogoToCompanyDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\CreateCompanyLegalDataDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\CreateCompanyOfficialDocumentDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\GeoCodingDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\RequestUpdateLegalCompanyDataRequestDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Modules\Company\CompanyCore\Events\CompanyLegalDataCreated;
use Modules\Company\CompanyCore\Models\CompanyLegalData;
use Modules\Company\CompanyCore\Repositories\CompanyAddressRepository;
use Modules\Company\CompanyCore\Repositories\CompanyLegalDataRepository;
use Modules\Company\CompanyCore\Repositories\CompanyOfficialDocumentRepository;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Country\Repositories\CityRepository;
use Modules\Country\Repositories\CountryRepository;
use Modules\Country\Repositories\StateRepository;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;
class CompanyProfileService
{
    public function __construct(
        private AdminRequestRepository            $adminRequestRepository,
        private FileUploadService                 $fileUploadService,
        private CompanyRepository                 $companyRepository,
        private CompanyLegalDataRepository        $companyLegalDataRepository,
        private CompanyAddressRepository          $companyAddressRepository,
        private CompanyOfficialDocumentRepository $companyOfficialDocumentRepository,
        private CityRepository                    $cityRepository,
        private StateRepository                   $stateRepository,
        private CountryRepository                 $countryRepository,

    )
    {
    }

    public function updateCompanyProfileRequest(UpdateOfficialCompanyDataRequestDTO $companyDataRequestDTO)
    {
        $adminRequest = $this->adminRequestRepository->createAdminRequestForCompanyOfficialData(
            userId: auth()->user()->id,
            data: $companyDataRequestDTO->toArray() + ["id" => $companyDataRequestDTO->getId()],
            requestType: "companyOfficialDataUpdate",
            action: ["ar" => "طلب تعديل البيانات الرسميه للشركة", "en" => "Company official data update request"],
            notes: $companyDataRequestDTO->getNotes()
        );
        return $adminRequest;

    }

    public function getTranslatedNighborhoodAndRoute($lat, $long)
    {
        $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$long}&language=ar&key=" . env('GOOGLE_MAPS_API_KEY'));
        $neighborhood = "";

        $route = "";
        foreach ($response['results'] as $key => $value) {
            if ($value['types'][0] == "political") {
                $neighborhood = $value["address_components"][0]["long_name"];
            }

            if ($value['types'][0] == "route") {
                $route = $value["address_components"][0]["long_name"];
            }
        }
        return [$neighborhood, $route];
    }

    public function geoCoding(GeoCodingDTO $geoCodingDTO)
    {

        $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json?latlng={$geoCodingDTO->getLatitude()},{$geoCodingDTO->getLongitude()}&language=en&key=" . env('GOOGLE_MAPS_API_KEY'));
        $neighborhood = "";
        $city = "";
        $state = "";

        $country = "";
        $postalCode = "";
        $route = "";
        foreach ($response['results'] as $key => $value) {
            if ($value['types'][0] == "political") {
                $neighborhood = $value["address_components"][0]["long_name"];
            }
            if ($value['types'][0] == "locality") {
                $city = $value["address_components"][0]["long_name"];
            }

            if ($value['types'][0] == "administrative_area_level_1") {
                $state = $value["address_components"][0]["long_name"];
            }

            if ($value['types'][0] == "country") {
                $country = $value["address_components"][0]["long_name"];
            }

            if ($value['types'][0] == "postal_code") {
                $postalCode = $value["address_components"][0]["long_name"];
            }
            if ($value['types'][0] == "route") {
                $route = $value["address_components"][0]["long_name"];
            }
        }
        [$country, $state, $city] = $this->getFromDB($country, $city, $state);
        if ($country) {

            if ((!request()->has("in_general")|| request()->in_general == 0) &&($country->id != $geoCodingDTO->getBranch()->address->country_id)) {
                throw new \Exception(__("validation.you-must-change-location-or-update-country"), 422);

            }
        }
        [$neighborhood, $route] = $this->getTranslatedNighborhoodAndRoute($geoCodingDTO->getLatitude(), $geoCodingDTO->getLongitude());

        return [
            $country,
            $state,
            $city,
            $neighborhood,
            $postalCode,
            $route,
        ];

    }

    private function getFromDB($country, $city, $state)
    {

        $city = trim(strtolower($city));
        $cityName = $city;

        $state = trim(strtolower($state));
        $stateName = $state;

        $country = trim(strtolower($country));
        $countryName = $country;

        $city = $this->cityRepository->findBySimplifiedWay($cityName);
        $state = $this->stateRepository->findBySimplifiedWay($stateName);
        $country = $this->countryRepository->findBySimplifiedWay($countryName);

        return [$country, $state, $city];


    }

    public function geoCodingUsingNationalAddressKSA(GeoCodingDTO $geoCodingDTO)
    {
        $url = "https://apina.address.gov.sa/NationalAddress/v3.1/Address/address-geocode?language=A&format=json&lat={$geoCodingDTO->getLatitude()}&long={$geoCodingDTO->getLongitude()}";
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, 'UTF-8');

        $headers = array(
            'api_key: 4c26a4bcbe11402e9782d13b155584dc',
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($curl);

        if ($resp !== false) {
            $resp = json_decode($resp, true, 512, JSON_INVALID_UTF8_IGNORE);
            return $resp;
        } else {
            // Handle curl error
            $error = curl_error($curl);
            curl_close($curl);
            throw new Exception($error);
        }

    }

    private function checkImage($image): int
    {
        $manager = new ImageManager(new Driver());
        $img = $manager->read($image);
        $width = $img->width();
        $height = $img->height();
        $white = 0;
        $color = 0;

// Iterate over each pixel
        for ($x = 0; $x < 2; $x++) {
            for ($y = 0; $y < $width; $y++) {
                $rgb = $img->pickColor($x, $y)->toArray();
                // Check if the pixel color is white (255, 255, 255)
                if ($rgb[0] == 255 || $rgb[1] == 255 || $rgb[2] == 255) {
                    $white++;
                } else {
                    $color++;
                }
            }
        }

        for ($x = 0; $x < 2; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = $img->pickColor($y, $x)->toArray();
                // Check if the pixel color is white (255, 255, 255)
                if ($rgb[0] == 255 || $rgb[1] == 255 || $rgb[2] == 255) {
                    $white++;
                } else {
                    $color++;
                }
            }
        }

        $percentage = ($white / ($white + $color)) * 100;


        if ($percentage > 70) {
            return 1;
        } else {

            return 0;
        }

    }

    public function validateLogo($image)
    {
        $errors = [];
        $flag = 1;
        // Ensure the image is uploaded
        if ($image == null) {
            array_push($errors, ["sentence" => "حجم الصورة يجب أن لا يتعدى 5 ميجابايت", "sub_title" => null, "status" => 0, 'validate' => 'required']);
            array_push($errors, ["sentence" => "أبعاد الصورة غير صحيحة. يجب أن تكون الأبعاد بين  1920*1080", "sub_title" => null, "status" => 0, "validate" => "required"]);
            array_push($errors, ["sentence" => "تأكد ان الخلفية بيضاء", "sub_title" => null, "status" => 0, "validate" => "required"]);
            return $errors;
        }


        $maxSizeInMB = 5;
        $fileSizeInMB = $image->getSize() / (1024 * 1024); // Convert bytes to MB

        if ($fileSizeInMB > $maxSizeInMB) {
            array_push($errors, ["sentence" => "حجم الصورة يجب أن لا يتعدى 5 ميجابايت", "sub_title" => null, "status" => 0, "validate" => "required"]);
            $flag=0;
        } else {
            array_push($errors, ["sentence" => "حجم الصورة يجب أن لا يتعدى 5 ميجابايت", "sub_title" => null, "status" => 1, 'validate' => 'required']);
        }

        // Get image dimensions
        list($width, $height) = getimagesize($image->getPathname());

        // Validate dimensions
        if ($width == 1920 && $height == 1080) {
            array_push($errors, ["sentence" => "أبعاد الصورة غير صحيحة. يجب أن تكون الأبعاد بين  1920*1080", "sub_title" => null, "status" => 1, "validate" => "required"]);
        } else {
            array_push($errors, ["sentence" => "أبعاد الصورة غير صحيحة. يجب أن تكون الأبعاد بين  1920*1080", "sub_title" => null, "status" => 0, "validate" => "required"]);
            $flag=0;
        }

        $result = $this->checkImage($image);

        if ($result === 0) {
            array_push($errors, ["sentence" => "تأكد ان الخلفية بيضاء", "sub_title" => null, "status" => 0, "validate" => "required"]);
            $flag=0;
        } else {
            array_push($errors, ["sentence" => "تأكد ان الخلفية بيضاء", "sub_title" => null, "status" => 1, "validate" => "required"]);
        }
        return [$errors,$flag];
    }


    public function assignLogo(AssignLogoToCompanyDTO $assignLogoToCompanyDTO)
    {

        $result = $this->checkImage($assignLogoToCompanyDTO->getLogo());
        if (!$result) {
            throw new \Exception(__("validation.logo-not-valid"), 400);
        }
        $company = $this->companyRepository->find($assignLogoToCompanyDTO->getId());
        $company->clearMediaCollection('logo');

        $this->fileUploadService->uploadFile($company, $assignLogoToCompanyDTO->getLogo(), 'company', 'logo');
        return $company;
    }

    public function updateLegalDataRequest(RequestUpdateLegalCompanyDataRequestDTO $companyDataRequestDTO)
    {
//        return

        $adminRequest = $this->adminRequestRepository->createAdminRequestForCompanyLegalData(
            userId: Uuid::fromString((string) auth()->user()->id),
            id: (string) $companyDataRequestDTO->getId(),
            data: $companyDataRequestDTO->toArray(),
            requestType: "companyLegalDataUpdate",
            action: ["ar" => "طلب تعديل البيانات القانونيه للشركة", "en" => "Company legal data update request"],
        );
        return $adminRequest;
    }


    public function createCompanyLegalData(CreateCompanyLegalDataDTO $companyLegalDataDTO)
    {
        $companyData =  $this->companyLegalDataRepository->createCompanyLegalData($companyLegalDataDTO->toArray(), $companyLegalDataDTO->getFile());
        event(new CompanyLegalDataCreated($companyData));
        return $companyData;
    }

    public function getCompanyLegalData(UuidInterface $id): CompanyLegalData
    {
        return $this->companyLegalDataRepository->find($id);
    }

    public function getCompanyAddress(UuidInterface $id): CompanyLegalData
    {
        return $this->companyAddressRepository->find($id);
    }

    public function getCompanyOfficialDocument(UuidInterface $id): CompanyLegalData
    {
        return $this->companyOfficialDocumentRepository->find($id);
    }

    public function createCompanyOfficialDocument(CreateCompanyOfficialDocumentDTO $companyOfficialDocumentDTO)
    {
        return $this->companyOfficialDocumentRepository->createCompanyOfficialDocument($companyOfficialDocumentDTO->toArray(), $companyOfficialDocumentDTO->getFiles());
    }


}
