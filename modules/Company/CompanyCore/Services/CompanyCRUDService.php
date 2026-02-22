<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Repositories\CompanyAddressRepository;
use Modules\Company\CompanyCore\DTO\CreateCompanyDTO;
use Modules\Company\CompanyCore\Jobs\CheckCompanyActivity;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\Events\CompanyCreatedEvent;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function PHPUnit\Framework\throwException;

class CompanyCRUDService
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;
    public function __construct(
        private CompanyRepository        $repository,
    )
    {
    }

    public function create(CreateCompanyDTO $createCompanyDTO): Company
    {
        $requestCompanyDTO = $createCompanyDTO->toArray();
        try {
            DB::beginTransaction();
            $company = $this->repository->createCompany($requestCompanyDTO);
            // dd($createCompanyDTO->companyFieldId);
            $company->companyFields()->sync($createCompanyDTO->companyFieldId); // ← Save array of UUIDs

//            CheckCompanyActivity::dispatch($company->id)->delay(now()->addHours(24));
            event(new CompanyCreatedEvent($company));
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 400);
        }

        return $company;
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            ['is_central_company' => 0],//TODO i think it will be like that ["id" ,"<>", tenant("id")] it will not present current company put will present other central company
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Company
    {
        return $this->repository->getCompany(
            id: $id,
        );
    }

    public function getCurrentCompanyLoggedIn()
    {
        //try {
            return $this->repository->getCurrentCompany();
        //} catch (\Exception $e) {
        //    throw new \Exception(__("validation.company-not-found"), 404);
        //
        //}
    }

    public function getCompanyByHost($host)
    {
        return $this->repository->getByHost($host);
    }

    public function getCompanyByIdentifierCode($identifierCode)
    {
        return $this->repository->getByIdentifierCode($identifierCode);
    }

//    public function export(?array $companyIds = null): string
//    {
//        $relations = [
//            'country',
//            'companyType',
//            'companyField',
//            'companyRegistrationType',
//            'generalManager',
//            'mainBranch',
//            'companyLegalData',
//            'companyOfficialDocuments',
//            'companyAddress',
//            'branches',
//            'companyFields'
//        ];
//
//        $companies = $companyIds
//            ? $this->repository->getCompaniesByIdsWithRelations($companyIds, $relations)
//            : $this->repository->getAllWithRelations($relations);
//
//        $csvHeader = [
//            'ID',
//            'Name',
//            'Username',
//            'Email',
//            'Phone',
//            'Country',
//            'Company Type',
//            'Company Field',
//            'Registration Type',
//            'General Manager',
//            'Is Active',
//            'Complete Data',
//            'Date Activated',
//            'Serial Number',
//            'Address',
//            'Legal Registration Number',
//            'Number of Branches'
//        ];
//
//        $csvData = [];
//        $csvData[] = $csvHeader;
//
//        foreach ($companies as $company) {
//            $csvData[] = [
//                $company->id,
//                $company->name,
//                $company->user_name,
//                $company->email,
//                $company->phone,
//                $company->country?->name ?? '',
//                $company->companyType?->name ?? '',
//                $company->companyField?->name ?? '',
//                $company->companyRegistrationType?->name ?? '',
//                $company->generalManager?->name ?? '',
//                $company->is_active ? 'Yes' : 'No',
//                $company->complete_data ? 'Yes' : 'No',
//                $company->date_activate,
//                $company->serial_no,
//                $company->companyAddress?->full_address ?? '',
//                $company->companyLegalData?->first()?->registration_number ?? '',
//                $company->branches?->count() ?? 0
//            ];
//        }
//
//
//
//        return createCSV($csvData);
//    }
    public function export(?array $companyIds = null, string $format = 'xlsx')
    {
        $relations = [
            'country',
            'companyType',
            'companyField',
            'companyRegistrationType',
            'generalManager',
            'mainBranch',
            'companyLegalData',
            'companyOfficialDocuments',
            'companyAddress',
            'branches',
            'companyFields'
        ];

        $companies = $companyIds
            ? $this->repository->getCompaniesByIdsWithRelations($companyIds, $relations)
            : $this->repository->getAllWithRelations($relations);

        return new \Modules\Company\CompanyCore\Exports\CompaniesExport($companies);
    }

    public function deleteLastCreated(): void
    {
        $lastCompany = $this->repository->getLastCreatedCompany();
        if (!$lastCompany) {
            throw new \Exception(__('No companies found'), 404);
        }
        $this->repository->deleteCompany(Uuid::fromString($lastCompany->id));
    }

    public function clearCahe()
    {
        // Get current company and branch using the trait method
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        // Clear cache for current company logged in
        $cacheKey = 'current_company_logged_in_' . $company->id . '_' . $branch->id;
        Cache::forget($cacheKey);
    }

    public function getClientCompanies(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->getClientCompanies($page, $perPage);
    }
}
