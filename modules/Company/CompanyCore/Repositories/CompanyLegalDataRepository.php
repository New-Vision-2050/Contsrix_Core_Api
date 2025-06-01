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
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Modules\Shared\Media\Services\FileDeletedService;

/**
 * @property Company $model
 * @method Company findOneOrFail($id)
 * @method Company findOneByOrFail(array $data)
 */
class CompanyLegalDataRepository extends BaseRepository
{
    public function __construct(
        CompanyLegalData $model,
        private FileUploadService $fileUploadService,
        private ManagementHierarchyRepository $managementHierarchyRepository,
        private FileDeletedService $fileDeletedService,
        )
    {
        parent::__construct($model);
    }

    public function createCompanyLegalData(array $data, $file): CompanyLegalData
    {
        try {
            DB::beginTransaction();
            $companyLegalData = $this->create($data);
            if (!is_null($file)) {
                $this->fileUploadService->uploadFile($companyLegalData, $file, "company");
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

            // Get optional branch_id from request
            $branchId = request()->get('branch_id');
            $companyId = request()->get('company_id')??request()->header('X-Tenant') ;
            // Get legal data scoped by branch if branch_id is provided
            $legalDataQuery = $this->model;

            if ($branchId) {
                $legalDataQuery = $legalDataQuery->where('management_hierarchy_id', $branchId);
            }else{
                $branch =  $this->managementHierarchyRepository->getMainBranchForCompany($companyId);
                $branchId = $branch->id;
                $legalDataQuery = $legalDataQuery->where('management_hierarchy_id', $branchId);
            }

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

                // Get the files to be deleted from the files array
                $filesToDelete = [];
                foreach ($item['files'] ?? [] as $fileEntry) {
                    if (isset($fileEntry['id'])) {
                        $filesToDelete[] = $fileEntry['id'];
                    }
                }

                // If specific files are marked for deletion, delete only those
                if (!empty($filesToDelete)) {
                    foreach ($filesToDelete as $fileId) {
                        $this->fileDeletedService->deleteFile($legalData, $fileId, 'upload');
                    }
                }

                // Add any new files
                foreach ($item['file'] ?? [] as $newFile) {
                    if (!is_string($newFile)) {
                        $this->fileUploadService->uploadFile($legalData, $newFile, 'company');
                    }
                }

            }
            DB::commit();

            if ($lastLegalData) {
                event(new CompanyLegalDataUpdated($lastLegalData->fresh()));
            }

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 409);
        }
    }


    public function delete( $id)
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
