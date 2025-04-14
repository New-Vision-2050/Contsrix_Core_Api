<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use http\Client\Curl\User;
use Illuminate\Support\Facades\DB;
use Modules\ActivityLog\Repositories\ActivityLogRepository;
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
class CompanyOfficialDocumentRepository extends BaseRepository
{
    public function __construct(CompanyOfficialDocument $model, private FileUploadService $fileUploadService,private ActivityLogRepository $activityLogRepository)
    {
        parent::__construct($model);
    }

    public function createCompanyOfficialDocument(array $data, $files): CompanyOfficialDocument
    {
        try {
            DB::beginTransaction();
            $companyOfficialDocument = $this->create($data);
            foreach ($files as $file) {
                $this->fileUploadService->uploadFile($companyOfficialDocument, $file, "company");
            }
            $this->activityLogRepository->createActivityLog(["action"=>["ar"=>"إنشاء","en"=>"create"],"date"=>Carbon::now()->format("Y-m-d H:i:s"), "user_id"=>auth()->user()->id,"requestable_id"=>$companyOfficialDocument->id , "requestable_type"=>CompanyOfficialDocument::class]);
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 409);

        }
        return $companyOfficialDocument;
    }

    public function updateCompanyOfficialDocument(UuidInterface $id, array $data, $files,$deletedFiles =[]): CompanyOfficialDocument
    {
        try {
            DB::beginTransaction();
            $companyOfficialDocument = $this->find($id);
            $companyOfficialDocument->update($data);
            foreach ($files as $file) {
                $this->fileUploadService->uploadFile($companyOfficialDocument, $file, "company");
            }
            foreach ($deletedFiles as $fileId) {
                $companyOfficialDocument->deleteMedia($fileId);
            }
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.update-not-successful"), 409);

        }
        return $companyOfficialDocument;
    }

    public function deleteMedia(UuidInterface $id , $fileId)
    {
        $this->find($id)->deleteMedia($fileId);
        return true;
    }






}
