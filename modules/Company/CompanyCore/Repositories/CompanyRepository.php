<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Domain;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;
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
        })->firstOrFail();

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
        if (strpos($url, '.') !== false) {
            $url = explode(".", $url);
            $subDomain = $url[0] . "-" . $username;
            $url = $subDomain . "." . $url[1].".".$url[2];
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
            $company = $this->create($data);
            $company->domains()->create([
                'domain' => $url,
            ]);
            DB::commit();
            return $company;
        } catch (\Exception $e) {

            DB::rollBack();
            throw new \Exception(__("validation.create-not-successful"), 500);
        }
    }

    public function updateCompany(UuidInterface $id, array $data): bool
    {

        try {
            DB::beginTransaction();
            $this->update($id, $data);
            $company = $this->find($id);
            Domain::query()->where("company_id", $company->id)->update(["domain" => $this->parseDomain($data["user_name"])]);
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
        return $this->model->where('registration_no', $registrationNo)
            ->where('registration_type_id', $registrationTypeId)
            ->exists();
    }

    public function isUserNameExists(string $userName): bool
    {
        return $this->model->where('user_name', $userName)->exists();
    }

    public function isNameExists(string $name): bool
    {
        return $this->model->where('name', $name)->exists();
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
            SUM(CASE WHEN is_active = 'active' THEN 1 ELSE 0 END) as active,
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
        return $this->model->get();
    }

    public function getCurrentCompany() : Company
    {
       [$company , $branch] =$this->declareCompanyAndBranchUsingRequest();
        return $this->model->where("id",$company->id)->with(["companyAddress"=>function ($query)use ($branch) {
            $query->where("management_hierarchy_id",$branch->id);
        },"companyLegalData"=>function ($query)use ($branch) {
            $query->where("management_hierarchy_id",$branch->id);

        },"companyOfficialDocuments"=>function ($query)use ($branch) {
            $query->where("management_hierarchy_id",$branch->id);}
        ])->first();
    }
}
