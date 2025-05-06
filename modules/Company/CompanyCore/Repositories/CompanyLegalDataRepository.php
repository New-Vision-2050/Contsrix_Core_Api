<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Events\CompanyLegalDataUpdated;
use Modules\Company\CompanyCore\Models\CompanyLegalData;
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
    public function __construct(CompanyLegalData $model, private FileUploadService $fileUploadService)
    {
        parent::__construct($model);
    }

    public function createCompanyLegalData(array $data, $file): CompanyLegalData
    {
        try {
            DB::beginTransaction();
            $companyLegalData = $this->create($data);
            $this->fileUploadService->uploadFile($companyLegalData, $file, "company");
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 409);

        }
        $companyLegalData->touch();
        return $companyLegalData;
    }

    public function updateCompanyLegalData( array $data)
    {
        try {
            DB::beginTransaction();
            foreach ($data as $item) {
               $legalData =  $this->findOneOrFail($item["id"]);
               $legalData->update(["start_date"=>$item["start_date"],"end_date"=>$item["end_date"]]);
               if(array_key_exists("file",$item) && !is_string($item["file"]))
               {
                   $legalData->clearMediaCollection('upload');//for replace with new media
                   $this->fileUploadService->uploadFile($legalData, $item["file"], "company");
               }

            }

            DB::commit();
            $legalData =$legalData->fresh();
            event(new CompanyLegalDataUpdated($legalData));
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 409);

        }
        return true;
    }




}
