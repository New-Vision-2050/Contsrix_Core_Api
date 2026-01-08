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
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Modules\Country\Repositories\CityRepository;
use Modules\Country\Repositories\CountryRepository;
use Modules\Country\Repositories\StateRepository;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\Shared\OpenRouter\Services\OpenRouterGeoService;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;

class CompanyProfileService
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

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
        private OpenRouterGeoService              $openRouterGeoService,
        private ManagementHierarchyRepository     $managementHierarchyRepository


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
            file: $companyDataRequestDTO->getFile(), notes: $companyDataRequestDTO->getNotes()
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
        $isAiSupported = false  ;
        $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json?latlng={$geoCodingDTO->getLatitude()},{$geoCodingDTO->getLongitude()}&language=en&key=" . env('GOOGLE_MAPS_API_KEY'));
        $neighborhood = "";
        $cities = [];
        $stateName = "";
        $countryShortName = "";
        $postalCode = "";
        $route = "";

        foreach ($response['results'] as $key => $value) {
            if ($value['types'][0] == "political") {
                $neighborhood = $value["address_components"][0]["long_name"];
            }
            if ($value['types'][0] == "administrative_area_level_2" || $value['types'][0] == "locality") {
                $cities[] = $value["address_components"][0]["long_name"];
            }

            if ($value['types'][0] == "administrative_area_level_1") {
                $stateName = $value["address_components"][0]["long_name"];
            }

            if ($value['types'][0] == "country") {
                $countryShortName = $value["address_components"][0]["short_name"];
            }

            if ($value['types'][0] == "postal_code") {
                $postalCode = $value["address_components"][0]["long_name"];
            }
            if ($value['types'][0] == "route") {
                $route = $value["address_components"][0]["long_name"];
            }
        }
        $cityName = implode(',',$cities);
        [$country, $state, $city] = $this->getFromDB($countryShortName, $cityName, $stateName);
        // Use OpenRouter to get location data for additional verification

        if ($country) {
            if ((!request()->has("in_general") || request()->in_general == 0) && ($country->id != $geoCodingDTO->getBranch()->address->country_id)) {
                throw new \Exception(__("validation.you-must-change-location-or-update-country"), 422);

            }
        }
        [$neighborhood, $route] = $this->getTranslatedNighborhoodAndRoute($geoCodingDTO->getLatitude(), $geoCodingDTO->getLongitude());

        if(empty($state) && !empty($city) && !empty($country)){
            $state = $city->state;
        }
        if(empty($state) || empty($city) || true){
            try {
                $data = $this->openRouterGeoService->getLocationFromOpenRouter(
                    $geoCodingDTO,
                    $this->stateRepository->getStatesWithCities(
                        $country->id
                    ),
                    $cityName,
                    $stateName
                );

                $locationData = (json_decode($data['data']['location_content'], true));

                if($locationData['recommended_city']['add_or_rename']) {
                    $state = $this->stateRepository->getState($locationData['state_id']);
                    $locationData['recommended_city']['state_code'] = $state->iso2;
                    $locationData['recommended_city']['state_id'] = $state->id;
                    $locationData['recommended_city']['country_id'] = $country->id;
                    $locationData['recommended_city']['country_code'] = $country->iso2;
                    $city = $this->cityRepository->createCity($locationData['recommended_city']);
                }
                else{
                    $city = $this->cityRepository->getCity($locationData['city_id']);
                }

                $state = $city->state;
                $isAiSupported = true;
            }catch (\Exception $e){
            }
        }
        // Return the location data along with comparison results
        $locationData = [
            $country,
            $state,
            $city,
            $neighborhood,
            $postalCode,
            $route,
            $isAiSupported
        ];
        return $locationData;
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

    /**
     * Get the company legal data
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompanyLegalDataForCompany()
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();

        $companyLegalData = $this->companyLegalDataRepository->findBy(["company_id" => $company->id, "management_hierarchy_id" => $branch->id]);
        return $companyLegalData;
    }

    /**
     * Get the company address with formatted country, state, and city data
     *
     * @return mixed
     */
    public function getCompanyAddressForCompany()
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();

        $address = $this->companyAddressRepository->findOneBy(["company_id" => $company->id, "management_hierarchy_id" => $branch->id]);

        if ($address) {
            $address->country_name = $address->country?->name;
            $address->state_name = $address->state?->name;
            $address->city_name = $address->city?->name;
            $address->country_lat = $address->country?->latitude;
            $address->country_long = $address->country?->longitude;
            $address->country_iso2 = $address->country?->iso2;
            unset($address->country);
            unset($address->state);
            unset($address->city);
        }

        return $address;
    }

    /**
     * Get the company official documents
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompanyOfficialDocumentsForCompany()
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        $companyOfficialDocuments = $this->companyOfficialDocumentRepository->findBy(["company_id" => $company->id, "management_hierarchy_id" => $branch->id]);
        return $companyOfficialDocuments;
    }

    /**
     * Get the company branches
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompanyBranchesForCompany()
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        $branches = $this->managementHierarchyRepository->findByWithRelations(["type" => "branch", "company_id" => $company->id], ["address"]);
        return $branches;
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

        // Coordinates of the 4 corners
        $corners = [
            [0, 0],                    // top-left
            [$width - 1, 0],           // top-right
            [0, $height - 1],          // bottom-left
            [$width - 1, $height - 1], // bottom-right
        ];

        foreach ($corners as [$x, $y]) {
            $rgb = $img->pickColor($x, $y)->toArray();
            if ($rgb[0] >= 240 && $rgb[1] >= 240 && $rgb[2] >= 240) { // allow slight variation
                $white++;
            } else {
                $color++;
            }
        }

        // Sample horizontal and vertical edges (skip corners)
        for ($i = 10; $i < $width - 10; $i += 20) {
            foreach ([0, $height - 1] as $y) {
                $rgb = $img->pickColor($i, $y)->toArray();
                if ($rgb[0] >= 240 && $rgb[1] >= 240 && $rgb[2] >= 240) {
                    $white++;
                } else {
                    $color++;
                }
            }
        }

        for ($i = 10; $i < $height - 10; $i += 20) {
            foreach ([0, $width - 1] as $x) {
                $rgb = $img->pickColor($x, $i)->toArray();
                if ($rgb[0] >= 240 && $rgb[1] >= 240 && $rgb[2] >= 240) {
                    $white++;
                } else {
                    $color++;
                }
            }
        }

        $percentage = ($white / ($white + $color)) * 100;

        return $percentage > 70 ? 1 : 0;

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
            $flag = 0;
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
            $flag = 0;
        }

        $result = $this->checkImage($image);

        if ($result === 0) {
            array_push($errors, ["sentence" => "تأكد ان الخلفية بيضاء", "sub_title" => null, "status" => 0, "validate" => "required"]);
            $flag = 0;
        } else {
            array_push($errors, ["sentence" => "تأكد ان الخلفية بيضاء", "sub_title" => null, "status" => 1, "validate" => "required"]);
        }
        return [$errors, $flag];
    }


    public function assignLogo(AssignLogoToCompanyDTO $assignLogoToCompanyDTO)
    {
        $logoFile = $assignLogoToCompanyDTO->getLogo();
        $company = $this->companyRepository->find($assignLogoToCompanyDTO->getId());

        if (!$logoFile instanceof \Illuminate\Http\UploadedFile || !$logoFile->isValid()) {
            $company->clearMediaCollection('logo');
            return $company;
        }

        $result = $this->checkImage($logoFile);
        if (!$result) {
            throw new \Exception(__("validation.logo-not-valid"), 400);
        }

        $company->clearMediaCollection('logo');

        $this->fileUploadService->uploadFile($company, $logoFile, 'company', 'logo', 'public', null, 'logo');

        return $company;
    }

    public function updateLegalDataRequest(RequestUpdateLegalCompanyDataRequestDTO $companyDataRequestDTO)
    {
//        return

        $adminRequest = $this->adminRequestRepository->createAdminRequestForCompanyLegalData(
            userId: Uuid::fromString((string)auth()->user()->id),
            id: (string)$companyDataRequestDTO->getId(),
            data: $companyDataRequestDTO->toArray(),
            requestType: "companyLegalDataUpdate",
            action: ["ar" => "طلب تعديل البيانات القانونيه للشركة", "en" => "Company legal data update request"],
        );
        return $adminRequest;
    }


    public function createCompanyLegalData(CreateCompanyLegalDataDTO $companyLegalDataDTO)
    {
        $companyData = $this->companyLegalDataRepository->createCompanyLegalData($companyLegalDataDTO->toArray(), $companyLegalDataDTO->getFiles());
        event(new CompanyLegalDataCreated($companyData));
        return $companyData;
    }

    public function createMultipleCompanyLegalData(array $companyLegalDataDTOs): array
    {
        $createdLegalData = [];
        
        foreach ($companyLegalDataDTOs as $companyLegalDataDTO) {
            $companyData = $this->companyLegalDataRepository->createCompanyLegalData(
                $companyLegalDataDTO->toArray(), 
                $companyLegalDataDTO->getFiles()
            );
            event(new CompanyLegalDataCreated($companyData));
            $createdLegalData[] = $companyData;
        }
        
        return $createdLegalData;
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
