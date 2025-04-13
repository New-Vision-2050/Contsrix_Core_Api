<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\CompanyLagalData;
use Modules\Company\CompanyCore\Models\Domain;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\CompanyCore\Models\Company;
use Carbon\Carbon;

/**
 * @property Company $model
 * @method Company findOneOrFail($id)
 * @method Company findOneByOrFail(array $data)
 */
class CompanyLegalDataRepository extends BaseRepository
{
    public function __construct(CompanyLagalData $model, private FileUploadService $fileUploadService)
    {
        parent::__construct($model);
    }

    public function createCompanyLegalData(array $data, $file):CompanyLagalData
    {
        try {
            DB::beginTransaction();
            $companyLegalData = $this->create($data);
            $this->fileUploadService->uploadFile($companyLegalData, $file, "company");
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
        }
        return $companyLegalData;
    }


}
