<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchySimpleDataPresenter;
use Modules\Company\ManagementHierarchy\Presenters\UserCanAccessManagementHierarchyPresenter;
use Modules\Company\ManagementHierarchy\Requests\AssignUsersToManagementHierarchyRequest;
use Modules\Company\ManagementHierarchy\Services\ManagementHierarchyCRUDService;
use Modules\Company\ManagementHierarchy\Services\UserCanAccessManagementHierarchyService;
use Modules\Setting\Services\SettingCRUDService;
use Modules\User\Models\User;
use Modules\User\Presenters\UserPresenter;

class UserCanAccessManagementHierarchyController extends Controller
{
    public function __construct(
        private UserCanAccessManagementHierarchyService $userAccessService,
        private SettingCRUDService                      $settingCRUDService,
        private ManagementHierarchyCRUDService          $managementHierarchyCRUDService,
    )
    {
    }

    /**
     * Assign users to a management hierarchy (branch)
     */
    public function assignUsers(AssignUsersToManagementHierarchyRequest $request): JsonResponse
    {
        try {
            $assignments = $this->userAccessService->assignUsersToManagementHierarchy(
                $request->createAssignUsersToManagementHierarchyDTO()
            );

            $presentedData = $assignments->map(function ($assignment) {
                $presenter = new UserCanAccessManagementHierarchyPresenter($assignment);
                return $presenter->getData();
            });

            return Json::items($presentedData->toArray());
        } catch (Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Get users assigned to a specific management hierarchy
     */
    public function getUsersByBranch(int $managementHierarchyId): JsonResponse
    {
        try {
            $assignments = $this->userAccessService->getUsersByManagementHierarchy($managementHierarchyId);


            return Json::items(UserPresenter::collection($assignments));
        } catch (Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Get management hierarchies accessible by a specific user
     */
    public function getBranchesByUser(string $userId): JsonResponse
    {
        try {

            $assignments = $this->userAccessService->getManagementHierarchiesByUser($userId);
            $user = User::find($userId);

            if (request()->has("role")) {
                $settings = $this->settingCRUDService->getShareClientAndBroker();
                if ((request()->role == 2 && $settings->where("key", "is_share_client")->first()->value == 1)
                    || (request()->role == 3 && $settings->where("key", "is_share_broker")->first()->value == 1)
                    || $user->is_owner == 1 || $user->email == "admin@constrix-nv.com"
                ) {
                    $assignments = $this->managementHierarchyCRUDService->listWithoutPagination("branch");
                }
            }

            $presentedData = ManagementHierarchySimpleDataPresenter::collection($assignments);

            return Json::items($presentedData);
        } catch (Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Remove a specific user from management hierarchy
     */
    public function removeUserFromBranch(string $userId, int $managementHierarchyId): JsonResponse
    {
        try {
            $result = $this->userAccessService->removeUserFromManagementHierarchy($userId, $managementHierarchyId);

            if ($result) {
                return Json::success(__('messages.user_access.removed_successfully'));
            }

            return Json::error(__('messages.user_access.not_found'), 404);
        } catch (Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Check if user has access to management hierarchy
     */
    public function checkUserAccess(string $userId, int $managementHierarchyId): JsonResponse
    {
        try {
            $hasAccess = $this->userAccessService->userHasAccess($userId, $managementHierarchyId);

            return Json::success([
                'user_id' => $userId,
                'management_hierarchy_id' => $managementHierarchyId,
                'has_access' => $hasAccess
            ]);
        } catch (Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Get all user access assignments with pagination
     */
    public function index(): JsonResponse
    {
        try {
            $page = (int)request()->get('page');
            $perPage = (int)request()->get('per_page', 10);

            $assignments = $this->userAccessService->getAllWithPagination($page, $perPage);

            $presentedData = $assignments->map(function ($assignment) {
                $presenter = new UserCanAccessManagementHierarchyPresenter($assignment);
                return $presenter->getData();
            });

            return Json::items($presentedData->toArray());
        } catch (Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }
}
