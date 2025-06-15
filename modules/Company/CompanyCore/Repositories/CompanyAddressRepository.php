<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Repositories;

use App\Exceptions\CustomException;
use BasePackage\Shared\Repositories\BaseRepository;
use http\Client\Curl\User;
use Illuminate\Support\Facades\DB;
use Modules\ActivityLog\Repositories\ActivityLogRepository;
use Modules\Company\CompanyCore\Models\CompanyAddress;
use Modules\Company\CompanyCore\Models\CompanyOfficialDocument;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\CompanyCore\Models\Company;
use Carbon\Carbon;

/**
 * @property Company $model
 * @method Company findOneOrFail($id)
 * @method Company findOneByOrFail(array $data)
 */
class CompanyAddressRepository extends BaseRepository
{
    public function __construct(CompanyAddress $model)
    {
        parent::__construct($model);
    }

    public function createCompanyAddress(array $data): CompanyAddress
    {
        return $this->create($data);
    }

    public function updateCompanyAddress(UuidInterface $id, array $data,array $latAndLong): CompanyAddress
    {
        try {
            DB::beginTransaction();
            $companyAddress = $this->find($id);
            $companyAddress->update($data);
            $companyAddress->branch()->update($latAndLong);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new CustomException(__("validation.update-not-successful"));
        }
        return $companyAddress->fresh();
    }



}
