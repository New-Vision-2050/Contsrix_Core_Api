<?php

namespace Modules\Company\CompanyCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Modules\ArchiveLibrary\Folder\Requests\UploadFileRequest;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Models\CompanyAddress;
use Modules\Company\CompanyCore\Models\Domain;
use Modules\Company\CompanyField\Database\Seeders\CompanyFieldSeederTableSeeder;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyType\Database\Seeders\CompanyTypeSeederTableSeeder;
use Modules\Company\CompanyRegistrationType\Database\Seeders\CompanyRegistrationTypeSeederTableSeeder;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\Country\Models\Country;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use Ranium\SeedOnce\Traits\SeedOnce;


class CompanyModulesSeederTableSeeder extends Seeder
{
    public function __construct(private FileUploadService $fileUploadService)
    {
    }

    use SeedOnce;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $this->call(CompanyFieldSeederTableSeeder::class);
        $this->call(CompanyTypeSeederTableSeeder::class);
        $this->call(CompanyRegistrationTypeSeederTableSeeder::class);

        $country = Country::first();
        $companyType = CompanyType::first();
        $companyField = CompanyField::first();
        $registrationType = CompanyRegistrationType::first();
        $general_manager = User::first();

        $namespace = Uuid::NAMESPACE_DNS;
        $id = Uuid::uuid5($namespace, "new-vision")->toString();

        $companyData = [
            'id' => $id,
            'user_name' => "new-vision",
            'email' => 'test@example.com',
            'phone' => '123456789',
            'country_id' => $country->id,
            'company_type_id' => $companyType->id,
            'company_field_id' => $companyField->id,
            'registration_type_id' => $registrationType->id,
            'general_manager_id' => $general_manager->id->toString(),
            'serial_no' => bin2hex(random_bytes(6)),
            "is_central_company" => 1
        ];

        $company = Company::insertOrIgnore($companyData);
        $company = Company::query()->find($id);
        $company->update(['name' => ["ar" => 'نيو فيجن', "en" => "new vision"]]);
        $path = resource_path()."/images/new-vision-logo.jpg";
        $file = new \Illuminate\Http\UploadedFile(
            $path,
            'new-vision-logo.jpg',
            null,
            null,
            true
        );
        $this->fileUploadService->uploadFile($company, $file, 'company', "logo");


        $domain = str_replace("be-", "", env("APP_URL"));

        Domain::query()->create([
            "company_id" => $id,
            "domain" => env("NEW_VISION_DOMAIN", $domain)
        ]);
        $branchId = Uuid::uuid5($namespace, "new-vision-branch")->toString();

         ManagementHierarchy::query()->insertOrIgnore(["id" => $branchId, "company_id" => $id, "name" => "الفرع الرئيسي", "type" => "branch","is_first_branch" => 1]);
        $mainBranch = ManagementHierarchy::query()->find($branchId);
        $companyAddressId = Uuid::uuid5($namespace, "new-vision-address")->toString();

        CompanyAddress::query()->create([
            "id"=>$companyAddressId,
            "company_id" => $id,
            "country_id" => $country->id,
            "management_hierarchy_id" => $mainBranch->id

        ]);

        $general_manager->update(['company_id' => $id]);

        $companyUserCompanyId = Uuid::uuid5($namespace, "new-vision-user-company")->toString();

        CompanyUserCompany::query()->insertOrIgnore([
            "id"=>$companyUserCompanyId,
            'company_id' => $id,
            'global_company_user_id' => $general_manager->global_company_user_id,
            'role' => CompanyUserRole::EMPLOYEE->value
        ]);

    }
}
