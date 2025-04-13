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
use Modules\Company\CompanyCore\DTO\CompanyProfile\GeoCodingDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\RequestUpdateLegalCompanyDataRequestDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Modules\Company\CompanyCore\Models\CompanyLegalData;
use Modules\Company\CompanyCore\Repositories\CompanyLegalDataRepository;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use Modules\Company\CompanyCore\DTO\CreateCompanyDTO;
use Modules\Company\CompanyCore\Jobs\CheckCompanyActivity;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class CompanyProfileService
{
    public function __construct(
        private AdminRequestRepository     $adminRequestRepository,
        private FileUploadService          $fileUploadService,
        private CompanyRepository          $companyRepository,
        private CompanyLegalDataRepository $companyLegalDataRepository,
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

    public function geoCoding(GeoCodingDTO $geoCodingDTO)
    {
        $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json?latlng={$geoCodingDTO->getLatitude()},{$geoCodingDTO->getLongitude()}&language=ar&key=" . env('GOOGLE_MAPS_API_KEY'));
        $poltical = "";
        $locality = "";
        $area = "";

        $country = "";
        $postalCode = "";
        $route = "";
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

            if ($value['types'][0] == "postal_code") {
                $postalCode = $value["address_components"][0]["long_name"];
            }
            if ($value['types'][0] == "route") {
                $route = $value["address_components"][0]["long_name"];
            }
        }
        return [
            "country" => $country,
            "state" => $area,
            "city" => $locality,
            "neighborhood" => $poltical,
            "postalCode" => $postalCode,
            "route" => $route,
        ];

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

    public function validateLogo(AssignLogoToCompanyDTO $assignLogoToCompanyDTO)
    {
        $errors = [];
        // Ensure the image is uploaded
        $image = $assignLogoToCompanyDTO->getLogo();

        // Check if the file is an image and is valid

        $maxSizeInMB = 5;
        $fileSizeInMB = $image->getSize() / (1024 * 1024); // Convert bytes to MB

        if ($fileSizeInMB > $maxSizeInMB) {
            array_push($errors, ["sentence" => "حجم الصورة يجب أن لا يتعدى 5 ميجابايت", "sub_title" => null, "status" => 0, "validate" => "required"]);
        } else {
            array_push($errors, ["sentence" => "حجم الصورة يجب أن لا يتعدى 5 ميجابايت", "sub_title" => null, "status" => 1, 'validate' => 'required']);
        }

        // Get image dimensions
        list($width, $height) = getimagesize($image->getPathname());

        // Validate dimensions
        if ($width == 1920 && $height == 1080) {
            array_push($errors, ["sentence" => "أبعاد الصورة غير صحيحة. يجب أن تكون الأبعاد بين  1920*1080", "sub_title" => null, "status" => 1]);
        } else {
            array_push($errors, ["sentence" => "أبعاد الصورة غير صحيحة. يجب أن تكون الأبعاد بين 468x704 و 477x714", "sub_title" => null, "status" => 0]);
        }

        $result = $this->checkImage($image);

        if ($result === 0) {
            array_push($errors, ["sentence" => "تأكد ان الخلفية بيضاء", "sub_title" => null, "status" => 0]);
        } else {
            array_push($errors, ["sentence" => "تأكد ان الخلفية بيضاء", "sub_title" => null, "status" => 1]);
        }
        return $errors;
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
            userId: auth()->user()->id,
            id:$companyDataRequestDTO->getId(),
            data: $companyDataRequestDTO->toArray(),
            requestType: "companyLegalDataUpdate",
            action: ["ar" => "طلب تعديل البيانات القانونيه للشركة", "en" => "Company legal data update request"],
        );
        return $adminRequest;
    }

    public function setAddress(RequestUpdateLegalCompanyDataRequestDTO $companyDataRequestDTO)
    {

    }

    public function createCompanyLegalData(CreateCompanyLegalDataDTO $companyLegalDataDTO)
    {
       return $this->companyLegalDataRepository->createCompanyLegalData($companyLegalDataDTO->toArray(), $companyLegalDataDTO->getFile());
    }

    public function getCompanyLegalData(UuidInterface $id ) : CompanyLegalData
    {
        return $this->companyLegalDataRepository->find($id);
    }

}
