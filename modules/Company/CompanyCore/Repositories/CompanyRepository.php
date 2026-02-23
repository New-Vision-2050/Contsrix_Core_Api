<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Domain;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\CompanyCore\Models\Company;
use Carbon\Carbon;

/**
 * @property Company $model
 * @method Company findOneOrFail($id)
 * @method Company findOneByOrFail(array $data)
 */
class CompanyRepository extends BaseRepository
{
    private $relations= ['country', 'companyType', 'companyField', 'companyRegistrationType', 'generalManager', "mainBranch", "companyLegalData.media", "companyOfficialDocuments.media", "companyOfficialDocuments.activityLogs", "companyAddress","owner","branches.address"];
    use PreDeclareComapnyAndBranchDependOnReqeuest;
    public function __construct(Company $model)
    {
        parent::__construct($model);
    }

    public function getCompanyList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getByHost($domain)
    {
        return $this->model->whereHas("domains",function ($query) use ($domain) {
            $query->where("domain", $domain);
        })->where('is_active',1)->firstOrFail();

    }

    public function getByIdentifierCode($identifierCode)
    {
        return $this->model->where("serial_no", $identifierCode)->where('is_active', 1)->firstOrFail();
    }

    public function getCompany(UuidInterface $id): Company
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    private function parseDomain($username)
    {
        $url = request()->header("X-DOMAIN")??request()->host();
        if (substr_count($url, '.') > 1) {
            $urlParts = explode(".", $url);
            $subDomain = $urlParts[0] . "-" . $username;
            $url = $subDomain . "." . $urlParts[1] . "." . $urlParts[2];
        } else {
            $url = $username . "." . $url;
        }
        return $url;
    }

    public function createCompany(array $data): Company
    {
        $url = $this->parseDomain($data["user_name"]);
        try {
            DB::beginTransaction();
            $company = $this->create(array_merge($data,["is_active"=>1,"date_activate"=>Carbon::now()->addMonths(3)->format("Y-m-d")]));
            $company->domains()->create([
                'domain' => $url,
            ]);
            DB::commit();
            return $company;
        } catch (\Exception $e) {

            DB::rollBack();
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function updateCompany(UuidInterface $id, array $data): bool
    {
        try {
            DB::beginTransaction();
            $this->update($id, $data);
            $company = $this->find($id);
            if(isset($data["user_name"]))
            {
                Domain::query()->where("company_id", $company->id)->update(["domain" => $this->parseDomain($data["user_name"])]);

            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.update-not-successful"), 500);

        }
    }

    public function deleteCompany(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function isEmailExists(string $email): bool
    {
        return $this->model->where('email', $email)->exists();
    }

    public function isPhoneExists(string $phone): bool
    {
        return $this->model->where('phone', $phone)->exists();
    }

    public function isRegistrationExists(string $registrationNo, string $registrationTypeId): bool
    {
        return $this->model->whereHas("companyLegalData",function ($query) use ($registrationNo,$registrationTypeId)
        {
            $query->where("registration_number", $registrationNo)->where("registration_type_id", $registrationTypeId);
        })->exists();
    }

    public function isUserNameExists(string $userName): bool
    {
        return $this->model->query()->withTrashed()->where('user_name', $userName)->exists();
    }

    public function isNameExists(string $name): bool
    {
        return $this->model->whereTranslatable('name', $name)->exists();
    }

    public function getInactiveCompanyIds(int $hours = 24, $companyId = null)
    {
        $inactiveTime = Carbon::now()->subHours($hours);

        return $this->model->where('check_activity', 0)
            ->where('created_at', '<', $inactiveTime)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id', $companyId);
            })
            ->pluck('id');
    }

    public function deleteCompaniesByIds(array $companyIds)
    {
        return $this->model->whereIn('id', $companyIds)->delete();
    }

    public function getCompanyStatistics($date = null)
    {
        return $this->model->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN complete_data IS NOT NULL THEN 1 ELSE 0 END) as complete_data,
            SUM(CASE WHEN date_activate IS NOT NULL THEN 1 ELSE 0 END) as data_activate
        ")
            ->when($date, fn($query) => $query->whereDate('created_at', '<=', $date))
            ->first();
    }

    public function whereIn($column, array $conditions)
    {
        $this->model->whereIn($column, $conditions);
        return $this;
    }

    public function get()
    {
        return $this->model->with($this->relations)->get();
    }

    public function getCurrentCompany() : Company
    {
       [$company , $branch] =$this->declareCompanyAndBranchUsingRequest();
        return $this->model->where("id",$company->id)->with(array_merge($this->relations ,["companyAddress"=>function ($query)use ($branch) {
            $query->where("management_hierarchy_id",$branch->id);
        },"companyLegalData"=>function ($query)use ($branch) {
            $query->where("management_hierarchy_id",$branch->id);

        },"companyOfficialDocuments"=>function ($query)use ($branch) {
            $query->where("management_hierarchy_id",$branch->id);}

        ]))->first();
    }



    public function getAllWithRelations(array $relations = []): Collection
    {
        $query = $this->model->with($relations)->where(['is_central_company' => 0]);
        if (method_exists($this->model, 'scopeFilter')) {
            $query->filter(request()->all());
        }
        return $query->get();
    }

    public function getCompaniesByIdsWithRelations(array $ids, array $relations = []): Collection
    {
        $query =  $this->model->whereIn('id', $ids)->where(['is_central_company' => 0])->with($relations);

        if (method_exists($this->model, 'scopeFilter')) {
            $query->filter(request()->all());
        }

        return $query->get();
    }

    public function getLastCreatedCompany(): ?Company
    {
        return $this->model->latest('created_at')->with($this->relations)->first();
    }

    /**
     * Count records by criteria
     */
    public function countWhere(array $conditions): int
    {
        return $this->model->where($conditions)->count();
    }

    /**
     * Sync packages for a company.
     */
    public function syncPackages(Company $company, array $syncData): void
    {
        $company->packages()->sync($syncData);
    }

    /**
     * Find company with packages.
     */
    public function findWithPackages(string $companyId): Company
    {
        return $this->model->with('packages')->findOrFail($companyId);
    }

    /**
     * Get client companies with owner relationship
     */
    public function getClientCompanies(?int $page, ?int $perPage = 10): array
    {
        $query = $this->model->where('is_client', 1)
            ->with($this->relations);

        $total = $query->count();
        
        if ($page && $perPage) {
            $data = $query->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();
        } else {
            $data = $query->get();
        }

        return [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $perPage ? (int) ceil($total / $perPage) : 1,
            ]
        ];
    }

    /**
     * Get broker companies with owner relationship
     */
    public function getBrokerCompanies(?int $page, ?int $perPage = 10): array
    {
        $query = $this->model->where('is_broker', 1)
            ->with($this->relations);

        $total = $query->count();
        
        if ($page && $perPage) {
            $data = $query->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();
        } else {
            $data = $query->get();
        }

        return [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $perPage ? (int) ceil($total / $perPage) : 1,
            ]
        ];
    }
}
