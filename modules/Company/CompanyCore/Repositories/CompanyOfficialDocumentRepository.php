<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
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
    public function __construct(CompanyOfficialDocument $model, private FileUploadService $fileUploadService)
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
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.create-not-successful"), 409);

        }
        return $companyOfficialDocument;
    }

    public function updateCompanyLegalData(UuidInterface $id, array $data, $file)
    {
        try {
            DB::beginTransaction();
            $this->findOneOrFail($id)->update($data);

            $companyLegalData = $this->find($id);
            $companyLegalData->clearMediaCollection('upload');//for replace with new media
            $this->fileUploadService->uploadFile($companyLegalData, $file, "company");
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 409);

        }
        return $companyLegalData;
    }




}
