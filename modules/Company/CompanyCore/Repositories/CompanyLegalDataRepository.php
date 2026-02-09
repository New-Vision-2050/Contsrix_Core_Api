<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Company\CompanyCore\Events\CompanyLegalDataCreated;
use Modules\Company\CompanyCore\Events\CompanyLegalDataUpdated;
use Modules\Company\CompanyCore\Models\CompanyLegalData;
use Modules\Company\CompanyCore\Models\Domain;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\CompanyCore\Models\Company;
use Carbon\Carbon;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Modules\Shared\Media\Services\FileDeletedService;

/**
 * @property Company $model
 * @method Company findOneOrFail($id)
 * @method Company findOneByOrFail(array $data)
 */
class CompanyLegalDataRepository extends BaseRepository
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function __construct(
        CompanyLegalData                      $model,
        private FileUploadService             $fileUploadService,
        private ManagementHierarchyRepository $managementHierarchyRepository,
        private FileDeletedService            $fileDeletedService,
    )
    {
        parent::__construct($model);
    }

    public function getCompanyLegalData($id): ?CompanyLegalData
    {
        return $this->model->find($id);
    }

    public function createCompanyLegalData(array $data, ?array $files): CompanyLegalData
    {
        try {
            DB::beginTransaction();
            $companyLegalData = $this->create($data);

            if (!is_null($files) && is_array($files) && count($files) > 0) {
                $registrationTypeName = CompanyRegistrationType::query()
                    ->where("id", $data["registration_type_id"])
                    ->first()
                    ->name ?? 'Legal Document';

                $folder = Folder::query()
                    ->withoutTenancy()
                    ->where("name","المستندات الرسمية")
                    ->where("company_id",$data["company_id"])
                    ->first();

                foreach ($files as $index => $file) {
                    if (!is_null($file)) {
                        $fileModel = File::create([
                            'name' => $registrationTypeName . ($index > 0 ? " ({$index})" : ''),
                            'folder_id' => $folder->id,
                            'access_type' => 'public',
                            'company_id' => $data["company_id"],
                            'start_date' => $data["start_date"],
                            'end_date' => $data["end_date"],
                            'management_hierarchy_id' => $data["management_hierarchy_id"],
                        ]);

                        $this->fileUploadService->uploadFile(
                            model: $companyLegalData,
                            file: $file,
                            filePath: "company",
                            fileId: $fileModel->id
                        );
                    }
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 409);
        }

        $companyLegalData->touch();
        return $companyLegalData;
    }

    public function updateCompanyLegalData(array $data = [])
    {
        try {
            DB::beginTransaction();


            [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
            $branchId = $branch->id;

            $legalDataQuery = $this->model->where('management_hierarchy_id', $branchId);

            $legalDataCollection = $legalDataQuery->get();

            if (empty($data)) {
                // Delete all legal data for this branch or all data if no branch specified
                $legalDataCollection->each(function ($legalData) {
                    $legalData->clearMediaCollection('upload');
                    $legalData->delete();
                });
                DB::commit();
                // return true;
            }

            $newIds = collect($data)->pluck('id')->all();

            $legalDataCollection->whereNotIn('id', $newIds)->each(function ($legalData) {
                $legalData->clearMediaCollection('upload');
                $legalData->delete();
            });

            $lastLegalData = null;

            foreach ($data as $item) {

                $legalData = $legalDataCollection->firstWhere('id', $item['id']);

                if (!$legalData) {
                    throw new \Exception("Legal data with ID {$item['id']} not found in the specified scope.", 404);
                }

                $legalData->update([
                    'start_date' => isset($item['start_date']) ? Carbon::parse($item['start_date'])->format('Y-m-d') : null,
                    'end_date' => isset($item['end_date']) ? Carbon::parse($item['end_date'])->format('Y-m-d') : null,
                ]);


                // Then collect all file IDs that should be kept (from the request)
                $fileIdsToKeep = [];
                if (isset($item['files'])) {
                    foreach ($item['files'] as $fileEntry) {
                        if (isset($fileEntry['id'])) {
                            $fileIdsToKeep[] = $fileEntry['id'];
                        }
                    }

                    // Only perform file deletion if 'files' array is present
                    // This ensures we keep files based on what's in the request
                    $this->fileDeletedService->deleteFile($legalData, $fileIdsToKeep, 'upload');
                }

                // Handle new file uploads (file field can be array or single file)
                if (isset($item["file"]) && !is_string($item["file"])) {
                    $registrationTypeName = CompanyRegistrationType::query()
                        ->where("id", $legalData->registration_type_id)
                        ->first()
                        ->name ?? 'Legal Document';

                    $folder = Folder::query()
                        ->withoutTenancy()
                        ->where("name","المستندات الرسمية")
                        ->where("company_id",$legalData->company_id)
                        ->first();

                    // Convert to array if single file
                    $files = is_array($item["file"]) ? $item["file"] : [$item["file"]];

                    foreach ($files as $index => $file) {
                        if (!is_null($file) && $file instanceof \Illuminate\Http\UploadedFile) {
                            $fileModel = File::create([
                                'name' => $registrationTypeName . (count($files) > 1 ? " (" . ($index + 1) . ")" : ''),
                                'folder_id' => $folder->id,
                                'access_type' => 'public',
                                'company_id' => $legalData->company_id,
                                'management_hierarchy_id' => $legalData->management_hierarchy_id,
                                'start_date' => $legalData->start_date,
                                'end_date' => $legalData->end_date,
                            ]);

                            $this->fileUploadService->uploadFile($legalData, $file, 'upload', fileId: $fileModel->id);
                        }
                    }
                }
                event(new CompanyLegalDataUpdated($legalData));
            }
            DB::commit();

//            if ($lastLegalData) {
//                event(new CompanyLegalDataUpdated($lastLegalData->fresh()));
//            }

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 409);
        }
    }


    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $legalData = $this->findOneOrFail($id);
            $legalData->clearMediaCollection('upload'); // Clear associated media files
            $legalData->delete(); // Delete the legal data record
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 409);
        }
    }

}
