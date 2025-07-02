<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Modules\Company\ManagementHierarchy\Handlers\DeleteManagementHierarchyHandler;
use Modules\Company\ManagementHierarchy\Handlers\MakeBranchMainHandler;
use Modules\Company\ManagementHierarchy\Handlers\UpdateBranchHandler;
use Modules\Company\ManagementHierarchy\Handlers\UpdateManagementHandler;
use Modules\Company\ManagementHierarchy\Handlers\UpdateManagementHierarchyHandler;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Presenters\DepartmentPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchySimpleDataPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyTreePresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyUserTreePresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementWithRelationsPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyLookupsPresenter;
use Modules\Company\ManagementHierarchy\Presenters\NonCopiedHierarchyPresenter;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Modules\Company\ManagementHierarchy\Requests\CreateBranchRequest;
use Modules\Company\ManagementHierarchy\Requests\CreateDepartmentRequest;
use Modules\Company\ManagementHierarchy\Requests\CreateManagementHierarchyRequest;
use Modules\Company\ManagementHierarchy\Requests\CreateManagementRequest;
use Modules\Company\ManagementHierarchy\Requests\CreateManagementWithRelationsRequest;
use Modules\Company\ManagementHierarchy\Requests\UpdateManagementWithRelationsRequest;
use Modules\Company\ManagementHierarchy\Requests\DeleteManagementHierarchyRequest;
use Modules\Company\ManagementHierarchy\Requests\GetLookupsRequest;
use Modules\Company\ManagementHierarchy\Requests\GetManagementHierarchyListRequest;
use Modules\Company\ManagementHierarchy\Requests\GetManagementHierarchyLookupRequest;
use Modules\Company\ManagementHierarchy\Requests\GetManagementHierarchyRequest;
use Modules\Company\ManagementHierarchy\Requests\GetNonCopiedHierarchiesRequest;
use Modules\Company\ManagementHierarchy\Requests\MakeBranchMainRequest;
use Modules\Company\ManagementHierarchy\Requests\Setting\CreateDepartmentWithRelationsRequest;
use Modules\Company\ManagementHierarchy\Requests\Setting\UpdateDepartmentWithRelationsRequest;
use Modules\Company\ManagementHierarchy\Requests\UpdateBranchRequest;
use Modules\Company\ManagementHierarchy\Requests\UpdateManagementHierarchyRequest;
use Modules\Company\ManagementHierarchy\Requests\UpdateManagementRequest;
use Modules\Company\ManagementHierarchy\Services\ManagementHierarchyCRUDService;
use Modules\Company\ManagementHierarchy\Services\ManagementHierarchyLookupsService;
use Modules\Company\ManagementHierarchy\Services\NonCopiedHierarchiesService;
use Modules\Company\ManagementHierarchy\Services\SettingManagementHierarchyService;
use Modules\JobTitle\Presenters\JobTitlePresenter;
use Modules\Shared\Currency\Requests\GetUsersLowLevelRequest;
use Modules\User\Models\User;
use Modules\User\Presenters\UserPresenter;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\Uuid;

class ManagementHierarchySettingController extends Controller
{
    public function __construct(
        private ManagementHierarchyCRUDService    $managementHierarchyService,
        private NonCopiedHierarchiesService       $nonCopiedHierarchiesService,
        private ManagementHierarchyLookupsService $lookupsService,
        private SettingManagementHierarchyService $settingManagementHierarchyService,

    )
    {
    }


    /**
     * Get management hierarchies where detail.is_copied = 0 with detail.managementHierarchy relationship
     *
     * @param GetNonCopiedHierarchiesRequest $request
     * @return JsonResponse
     */
    public function getNonCopiedHierarchies(GetNonCopiedHierarchiesRequest $request)
    {
        try {
            $dto = $request->createGetNonCopiedHierarchiesDTO();

            $hierarchies = $this->nonCopiedHierarchiesService->getNonCopiedHierarchies($dto);

            return Json::items(
                NonCopiedHierarchyPresenter::collection($hierarchies['data']),
                $hierarchies['pagination'] ?? []
            );
        } catch (Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Get all management hierarchies where detail.is_copied = 0 without pagination
     *
     * @param GetNonCopiedHierarchiesRequest $request
     * @return JsonResponse
     */
    public function getAllNonCopiedHierarchies(GetNonCopiedHierarchiesRequest $request): JsonResponse
    {
        try {
            $hierarchies = $this->nonCopiedHierarchiesService->getAllNonCopiedHierarchies();


            return Json::items(
                NonCopiedHierarchyPresenter::collection($hierarchies)
            );
        } catch (Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Create a new management with job types, job titles, and branches relations
     */
    public function createManagementWithLookupsForChoise(CreateManagementWithRelationsRequest $request): JsonResponse
    {
        $createManagementWithRelationsDTO = $request->createCreateManagementWithRelationsDTO();
        $sourceManagementHierarchy = $this->managementHierarchyService->createManagementWithLookupsForChoise($createManagementWithRelationsDTO);

        return Json::item(
            (new ManagementWithRelationsPresenter($sourceManagementHierarchy))->getData(),
        );

    }

    /**
     * Update a management with job types, job titles, and branches relations
     */
    public function updateManagementWithLookupsForChoise(UpdateManagementWithRelationsRequest $request): JsonResponse
    {
        $updateManagementWithRelationsDTO = $request->createUpdateManagementWithRelationsDTO();
        $sourceManagementHierarchy = $this->managementHierarchyService->updateManagementWithLookupsForChoise($updateManagementWithRelationsDTO);

        return Json::item(
            (new ManagementWithRelationsPresenter($sourceManagementHierarchy))->getData(),
        );
    }

    public function getJobTitles(Request $request): JsonResponse
    {
        try {
            $jobTypeIds = $request->input('job_type_ids');
            if ($jobTypeIds !== null) {
                if (str_contains($jobTypeIds, ",")) {
                    $jobTypeIds = explode(',', $jobTypeIds);
                } else {
                    $jobTypeIds = [$jobTypeIds];
                }
            }
            $jobTitles = $this->lookupsService->getJobTitles($jobTypeIds);
            return Json::items(JobTitlePresenter::collection($jobTitles));
        } catch (Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Get lookups data for management hierarchy creation
     */
    public function getLookupsForChoices(GetLookupsRequest $request): JsonResponse
    {
        try {
            $jobTypeIds = $request->input('job_type_ids');
            if ($jobTypeIds !== null) {
                if (str_contains($jobTypeIds, ",")) {
                    $jobTypeIds = explode(',', $jobTypeIds);
                } else {
                    $jobTypeIds = [$jobTypeIds];
                }
            }
            $lookups = $this->lookupsService->getAllLookups($jobTypeIds);

            return Json::items(JobTitlePresenter::collection($lookups['job_titles']));
        } catch (Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }


    /**
     * create a new department with Managements
     */
    public function createDepartmentWithManagementsForDropDown(CreateDepartmentWithRelationsRequest $createDepartmentWithRelationsRequest)
    {
        $createDepartmentWithRelationsDTO = $createDepartmentWithRelationsRequest->createCreateDepartmentWithRelationsDTO();
        $managementHierarchy = $this->settingManagementHierarchyService->createDepartmentWithRealtion($createDepartmentWithRelationsDTO);

        return Json::item(
            (new ManagementWithRelationsPresenter($managementHierarchy))->getData(),
        );

    }

    /**
     * Update a department with managements for dropdown
     */
    public function updateDepartmentWithManagementsForDropDown(UpdateDepartmentWithRelationsRequest $updateDepartmentWithRelationsRequest)
    {
        $updateDepartmentWithRelationsDTO = $updateDepartmentWithRelationsRequest->createUpdateDepartmentWithRelationsDTO();
        $managementHierarchy = $this->settingManagementHierarchyService->updateDepartmentWithRelation($updateDepartmentWithRelationsDTO);

        return Json::item(
            (new ManagementWithRelationsPresenter($managementHierarchy))->getData(),
        );
    }
}
