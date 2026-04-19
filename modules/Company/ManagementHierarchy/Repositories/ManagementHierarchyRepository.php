<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Repositories;

use App\Exceptions\CustomException;
use App\Scopes\CustomTenantScope;
use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\DTO\AssignUsersToManagementHierarchyDTO;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchyDetail;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchyDetailManager;

use Modules\Company\ManagementHierarchy\Models\SourceManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\User\Models\User;
use Ramsey\Uuid\UuidInterface;
use function PHPUnit\Framework\throwException;
use function Symfony\Component\String\s;

/**
 * @property ManagementHierarchy $model
 * @method ManagementHierarchy findOneOrFail($id)
 * @method ManagementHierarchy findOneByOrFail(array $data)
 */
class ManagementHierarchyRepository extends BaseRepository
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public $nextId;

    public function __construct(ManagementHierarchy $model, private UserCanAccessManagementHierarchyRepository $userCanAccessManagementHierarchyRepository)
    {
        parent::__construct($model);
        $last = $model->query()
            ->orderBy("id", "desc")
            ->withoutGlobalScope(CustomTenantScope::class)
            ->first();

        $this->nextId = $last ? $last->id + 1 : 1;
    }

    public function getManagementHierarchyList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getAll($type = null)
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();

        if (request()->has("branch_id")) {
            return $this->model->filter(request()->all())
                ->where("company_id", $company->id)
                ->where("type", "management")
                ->whereHas("detail",
                    function ($query) {
                        $query->where("branch_id", request()->branch_id);
                    }
                )->get();
        }

        $query = $this->model->filter(request()->all())->when($type !=null ,function ($query) use ($type) {
            $query->where("type", $type);
        });

        if (request()->has("parent_children_id")) {
            $parentNode = $this->model->where("id", request()->parent_children_id)
                ->where("company_id", $company->id)
                ->first();

            if ($parentNode) {
                $query->whereSelfOrDescendantOf($parentNode);
            }
        }

        if (request()->has("ignore_branch_id")) {
            $ignoredNode = $this->model->where("id", request()->ignore_branch_id)
                ->where("company_id", $company->id)
                ->first();

            if ($ignoredNode) {
                $query->where(function ($subQuery) use ($ignoredNode) {
                    $subQuery->whereDoesntHave("detail", function ($q) {
                        $q->where("branch_id", request()->ignore_branch_id);
                    })
                        ->whereHas("detail.sourceManagementHierarchy.relatedBranches", function ($q) {
                            $q->where("branch_id", request()->ignore_branch_id);
                        });
                });
            }
        }

        return $query->where("company_id", $company->id)->get();
    }

    public function getTree()
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        $managementHierarchy = null;
        if (request()->has("id")) {
            $managementHierarchy = $this->model->where("id", request()->id)->where("company_id", $company->id)->first();

        }

        return $this->model->where("company_id", $company->id)->with(["user.companyUser.media", "users", "directUserChildren", "detail"])
            ->when(request()->has("type"), function ($query) {
                if (request()->type == "management") {
                    $query->where("type", "management");
                }

            })
            ->when(request()->has("id") && $managementHierarchy, function ($query) use ($managementHierarchy) {
                $query->whereSelfOrDescendantOf($managementHierarchy);

            })->get()->tree();
    }

    public function getManagementHierarchy(int $id): ManagementHierarchy
    {
        return $this->findOneByOrFail([
            'id' => $id,
        ]);
    }

    public function getMainBranchForCompany($id): ManagementHierarchy
    {
        return $this->findOneBy([
            "company_id" => $id,
            "parent_id" => null,
            "type" => "branch"
        ]);
    }

    public function createBranch(array $branchData, array $addressData,): ManagementHierarchy
    {
        try {
            DB::beginTransaction();
            tenancy()->initialize((string)$branchData["company_id"]);
            $managementHierarchy = $this->create($branchData + ["id" => $this->nextId]);

            $this->nextId = $this->nextId + 1;
            //here we will clone main management

            if (isset($branchData["is_main"]) && $branchData["is_main"] == 1) {
                $sourceManagementHierarchy = $this->createSourceManagementHierarchy(["name" => "الادارة العامة", "type" => "management", "company_id" => $managementHierarchy->company_id]);

                $this->createManagement(["company_id" => $managementHierarchy->company_id, "parent_id" => $managementHierarchy->id, "is_main" => 1, "name" => " الادارة العامة  ", "type" => "management", "manager_id" => $managementHierarchy->manager_id, "phone" => $managementHierarchy->phone, "phone_code" => $managementHierarchy->phone_code, "email" => $managementHierarchy->email], ["description" => "الادارة العامة", "branch_id" => $managementHierarchy->id, "reference_department_id" => $sourceManagementHierarchy->id, "is_copied" => 1], []);

            } else {
                $users = User::query()->where("id", $branchData["manager_id"])->orWhere("is_owner",1)->pluck("id")->toArray();
                $sourceManagementHierarchy = SourceManagementHierarchy::query()->first();
                $this->userCanAccessManagementHierarchyRepository->assignUsersToManagementHierarchy(new AssignUsersToManagementHierarchyDTO(branchId: $managementHierarchy->id, userIds: $users));


                $this->createManagement(["company_id" => $managementHierarchy->company_id, "parent_id" => $managementHierarchy->id, "is_main" => 1, "name" => " الادارة العامة لفرع $managementHierarchy->name ", "type" => "management", "manager_id" => $managementHierarchy->manager_id, "phone" => $managementHierarchy->phone, "phone_code" => $managementHierarchy->phone_code, "email" => $managementHierarchy->email], ["description" => "الادارة العامة", "branch_id" => $managementHierarchy->id, "reference_department_id" => $sourceManagementHierarchy->id, "is_copied" => 1], []);
            }


            $managementHierarchy->address()->create($addressData + ["company_id" => $managementHierarchy->company_id]);

            if (isset($branchData['default_constraint_id']) && $branchData['default_constraint_id'] !== null) {
                    $managementHierarchy->attendanceConstraints()->attach($branchData['default_constraint_id'], [
                        'is_default' => true
                    ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 500);

        }
        return $managementHierarchy;
    }

    public function createManagementHierarchy(array $managementHierarchyData): ManagementHierarchy
    {

        $managementHierarchy = $this->create($managementHierarchyData + ["id" => $this->nextId]);
        return $managementHierarchy;
    }

    public function getSourceManagementHierarchy($sourceId)
    {
        return SourceManagementHierarchy::query()->find($sourceId);
    }

    public function createManagement(array $managementData, array $managementDetail, ?array $deputyManagers): ManagementHierarchy
    {

//        try {
        DB::beginTransaction();
        $parentManagementHierarchy = $this->findOneBy(['id' => $managementData['parent_id']])->first();
        $sourceManagementHierarchy = SourceManagementHierarchy::query()->first();
        if ($parentManagementHierarchy != null && $parentManagementHierarchy->type == "management") {
            $sourceManagementHierarchy = $parentManagementHierarchy->detail->sourceManagementHierarchy;
        }
//            $managementHierarchyId = ManagementHierarchyDetail::where('reference_department_id', $sourceManagementHierarchy->id)->value('management_hierarchy_id');
//            $managementData['parent_id'] = $managementHierarchyId;
        $managementHierarchy = $this->create($managementData + ["id" => $this->nextId]);
        $detail = $managementHierarchy->detail()->create(array_merge(["reference_department_id" => $sourceManagementHierarchy->id, "is_copied" => 1], $managementDetail));

        if ($deputyManagers != null && count($deputyManagers) > 0) {
            foreach ($deputyManagers as $deputyManager) {
                ManagementHierarchyDetailManager::create([
                    "deputy_manager_id" => $deputyManager,
                    "management_hierarchy_detail_id" => $detail->id
                ]);
            }

        }
        DB::commit();
//        } catch (\Exception $e) {
//            DB::rollBack();
//            throw new \Exception($e->getMessage(), 500);
//
//        }
        return $managementHierarchy;
    }


    public function createDepartment(array $departmentData, array $departmentDetail, ?array $deputyManagers): ManagementHierarchy
    {

        try {
            DB::beginTransaction();
            $managementHierarchy = $this->create($departmentData + ["id" => $this->nextId]);
            $detail = $managementHierarchy->detail()->create(array_merge(["reference_department_id" => $managementHierarchy->id, "is_copied" => 0], $departmentDetail));
            if ($deputyManagers != null && count($deputyManagers) > 0) {
                foreach ($deputyManagers as $deputyManager) {
                    ManagementHierarchyDetailManager::create(["deputy_manager_id" => $deputyManager, "management_hierarchy_detail_id" => $managementHierarchy->detail->id]);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 500);

        }
        return $managementHierarchy;
    }

    public function updateManagementHierarchy(int $id, array $branchData, array $addressData): bool
    {
        try {
            DB::beginTransaction();
            $managementHierarchy = $this->find($id);
            if (isset($branchData["parent_id"]) && $branchData["parent_id"] != null && $id != $branchData["parent_id"]) {
                $flag = 0;
                $swapBranch = $this->findOneBy(["id" => $branchData["parent_id"]]);
                //check circular
                while ($swapBranch != null) {

                    if ($swapBranch->id == $id) {

                        $flag = 1;
                        break;
                    }
                    $swapBranch = $swapBranch->parent;
                }
                $swapBranch = $this->findOneBy(["id" => $branchData["parent_id"]]);

                if ($flag == 1) {
                    //circular
                    $parentId = $managementHierarchy->parent_id;
                    $swapBranch->update(["parent_id" => null]);
                    $managementHierarchy->update(["parent_id" => $branchData["parent_id"]]);
                    $swapBranch->update(["parent_id" => $parentId]);
                } else {
                    $managementHierarchy->update(["parent_id" => $branchData["parent_id"]]);
                }
            }
            unset($branchData["parent_id"]);

            $managementHierarchy->update($branchData);
            $managementHierarchy->fresh();

            $managementHierarchy->address()->update($addressData);

            // Dispatch event for branch location update instead of direct method call
            if (isset($branchData['latitude']) && isset($branchData['longitude'])) {
                event(new \Modules\Company\ManagementHierarchy\Events\BranchLocationUpdatedEvent(
                    $id,
                    $branchData['latitude'],
                    $branchData['longitude']
                ));
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 500);

        }
        return true;
    }

    public function updateManagement(int $id, array $managementData, array $managementDetail, ?array $deputyManagers): bool
    {
        try {
            DB::beginTransaction();

            // Update management hierarchy
            $managementHierarchy = $this->findOneOrFail($id);
            $managementHierarchy->update($managementData);

            // Update management detail
            if ($managementHierarchy->detail) {
                $managementHierarchy->detail->update($managementDetail);
            } else {
                $managementHierarchy->detail()->create($managementDetail);
            }

            // Delete existing deputy managers and create new ones
            if ($managementHierarchy->detail) {
                $detailId = $managementHierarchy->detail->id;
                ManagementHierarchyDetailManager::where('management_hierarchy_detail_id', $detailId)->delete();

                // Create new deputy managers
                if ($deputyManagers != null && count($deputyManagers) > 0) {
                    foreach ($deputyManagers as $deputyManager) {
                        ManagementHierarchyDetailManager::create([
                            'deputy_manager_id' => $deputyManager,
                            'management_hierarchy_detail_id' => $detailId
                        ]);
                    }
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * Check if a management hierarchy has any children
     *
     * @param int $id The ID of the management hierarchy to check
     * @return bool True if it has children, false otherwise
     */
    public function hasChildren(int $id): bool
    {
        $managementHierarchy = $this->findOneOrFail($id);
        //not allow to delete main management or main branch pu by default main branch has children
        if ($managementHierarchy->is_main == 1) {
            return true;
        }

        // Check for direct management hierarchy children
        $childrenCount = $this->model->where('parent_id', $id)->count();
        if ($childrenCount > 0) {
            return true;
        }

        // Check for user children/employees
        $userChildrenCount = $managementHierarchy->directUserChildren()->count();
        return $userChildrenCount > 0;
    }

    public function deleteManagementHierarchy(int $id): bool
    {
        return $this->delete($id);
    }

    public function makeMainBranch(int $id, int $branchId)
    {
        $mainBranch = $this->find($id);
        $otherMainBranchesCount = $this->model->where('id', "<>", $id)->where("company_id", $mainBranch->company_id)->where("type", "branch")->whereNull("parent_id")->count();


        $mainBranch->update(["is_main" => false]);

        $newMainBranch = $this->find($branchId);
        $newMainBranch->update(["is_main" => true]);

        if ($otherMainBranchesCount) {
            // If there are other main branches, simply update the parent_id of the old main branch
            $mainBranch->update(["parent_id" => $branchId]);
        } else {
            try {
                DB::beginTransaction();
                // If this is a swap operation
                $newMainBranch = $this->find($branchId);

                // Store the original parent of the new main branch
                $originalParentId = $newMainBranch->parent_id;

                // First, detach the new main branch from its parent
                $newMainBranch->parent_id = null;
                $newMainBranch->save();


                // Then update the old main branch's parent to be the original parent of the new main branch
                $mainBranch->update(["parent_id" => $branchId]);
                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();

                throw new \Exception($e->getMessage(), 500);
            }


        }
    }

    /**
     * Get detail for a management hierarchy
     *
     * @param int|string $managementHierarchyId
     * @return \Modules\Company\ManagementHierarchy\Models\ManagementHierarchyDetail|null
     */
    public function getDetail($managementHierarchyId)
    {
        return ManagementHierarchyDetail::where('management_hierarchy_id', $managementHierarchyId)->first();
    }


    /**
     * Get deputy managers for a management hierarchy detail
     *
     * @param string|int $detailId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDeputyManagers($detailId)
    {
        return ManagementHierarchyDetailManager::where('management_hierarchy_detail_id', $detailId)->get();
    }

    /**
     * Delete deputy managers for a management hierarchy detail
     *
     * @param string|int $detailId
     * @return bool
     */
    public function deleteDeputyManagers($detailId)
    {
        return ManagementHierarchyDetailManager::where('management_hierarchy_detail_id', $detailId)->delete();
    }

    /**
     * Get linked departments by reference department ID
     *
     * @param string|int $referenceDepartmentId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLinkedDepartmentsByReference($referenceDepartmentId)
    {
        $linkedDepartmentIds = ManagementHierarchyDetail::where('reference_department_id', $referenceDepartmentId)
            ->pluck('management_hierarchy_id');

        if ($linkedDepartmentIds->isEmpty()) {
            return collect([]);
        }

        return $this->model
            ->whereIn('id', $linkedDepartmentIds)
            ->with(['detail', 'parent'])
            ->get();
    }

    /**
     * Get count statistics for hierarchies by type
     *
     * @param string $type The hierarchy type (branch, management, department)
     * @param mixed $companyId The company ID
     * @return array
     */
    public function getHierarchyCountStatistics(string $type, $companyId): array
    {
        // Total count of the hierarchy type
        $totalCount = $this->model
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->count();

        // Count of hierarchy items used in user records
        $usedCount = $this->model
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->whereHas('directUserChildren')
            ->count();

        // Count of hierarchy items not used in user records
        $unusedCount = $totalCount - $usedCount;

        return [
            'total_count' => $totalCount,
            'used_count' => $usedCount,
            'unused_count' => $unusedCount
        ];
    }

    /**
     * Get all lower level users in the management hierarchy tree for a specific user
     * First checks if the user is a manager or deputy manager of any hierarchy
     * get user in all hirarchy recuservly and make it unique to omit redduncey
     *
     * @param int $userId The ID of the user
     */
    public function getUserLowerLevels(UuidInterface $userId)
    {
        // Get the user in a single query
        $user = User::findOrFail($userId);
        $lowerUsers = collect([$user]); // Add the user as an option

        // Find all hierarchies where the user has management responsibilities in a single query
        $hierarchyQuery = $this->model->where(function ($query) use ($userId) {
            // Where user is a direct manager
            $query->where('manager_id', $userId)
                // Or where user has a related management hierarchy
                ->orWhere('id', function ($subQuery) use ($userId) {
                    $subQuery->select('management_hierarchy_id')
                        ->from('users')
                        ->where('id', $userId)
                        ->whereNotNull('management_hierarchy_id');
                });
        });

        // Execute query once to get hierarchies where user is manager or has relation
        $managementHierarchies = $hierarchyQuery->get();

        // Get deputy manager hierarchies in a separate efficient query (can't be merged easily)
        $deputyHierarchyIds = DB::table('management_hierarchy_deputy_managers')
            ->join('management_hierarchy_details', 'management_hierarchy_details.id', '=', 'management_hierarchy_deputy_managers.management_hierarchy_detail_id')
            ->where('deputy_manager_id', $userId)
            ->pluck('management_hierarchy_details.management_hierarchy_id');

        // If deputy hierarchies exist, fetch them with a single query
        if ($deputyHierarchyIds->isNotEmpty()) {
            $deputyHierarchies = $this->model->whereIn('id', $deputyHierarchyIds)->get();
            $managementHierarchies = $managementHierarchies->merge($deputyHierarchies);
        }

        if ($managementHierarchies->isEmpty()) {
            return $lowerUsers;
        }

        // Collect all descendant hierarchy IDs using the model's descendants method
        $descendantIds = collect();

        foreach ($managementHierarchies as $hierarchy) {
            // Add the current hierarchy ID
            $descendantIds->push($hierarchy->id);

            // Fetch all descendants and add their IDs
            $descendants = $hierarchy->descendants()->get();
            if ($descendants->isNotEmpty()) {
                $descendantIds = $descendantIds->merge($descendants->pluck('id'));
            }
        }

        // Make sure we have unique IDs
        $descendantIds = $descendantIds->unique()->values()->toArray();

        // Now that we have all hierarchy IDs (original + descendants),

        // 1. Get managers with a single query
        $managerUsers = User::whereIn('id', function ($query) use ($descendantIds) {
            $query->select('manager_id')
                ->from('management_hierarchies')
                ->whereIn('id', $descendantIds)
                ->whereNotNull('manager_id');
        })
            ->where('id', '!=', $userId)
            ->get();

        // 2. Get deputy managers with a single query
        $deputyUsers = User::whereIn('id', function ($query) use ($descendantIds) {
            $query->select('deputy_manager_id')
                ->from('management_hierarchy_deputy_managers')
                ->join('management_hierarchy_details', 'management_hierarchy_details.id', '=', 'management_hierarchy_deputy_managers.management_hierarchy_detail_id')
                ->whereIn('management_hierarchy_details.management_hierarchy_id', $descendantIds);
        })
            ->where('id', '!=', $userId)
            ->get();

        // 3. Get direct user children with a single query
        $directUserChildren = User::whereNotNull('management_hierarchy_id')
            ->whereIn('management_hierarchy_id', $descendantIds)
            ->where('id', '!=', $userId)
            ->get();

        // Merge all users and get unique result
        $allUsers = $lowerUsers
            ->merge($managerUsers)
            ->merge($deputyUsers)
            ->merge($directUserChildren)
            ->unique('id');

        // Apply name filter if provided in request
        $name = request()->input('name');
        if ($name) {
            $allUsers = $allUsers->filter(function ($user) use ($name) {
                return stripos($user->first_name, $name) !== false ||
                       stripos($user->last_name, $name) !== false;
            });
        }

        return $allUsers;
    }

    /**
     * Get management hierarchies where detail.is_copied = 0 with detail.managementHierarchy relationship (paginated)
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getNonCopiedHierarchies(int $page = 1, int $perPage = 10): array
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        $filters = request()->all();
        if (!array_key_exists('type', $filters)) {
            $filters["type"] = "management";
        }


        $query = SourceManagementHierarchy::query()->with(['details.managementHierarchy', 'company'])
            ->when(isset($filters["type"]), function ($query) use ($filters) {
                $query->where("type", $filters["type"]);
            })->when(request()->has("ignore_branch_id"), function ($query) {
//                $query->where(function ($q) {
//                    $q->whereHas("details", function ($query) {
//                        $query->where("branch_id", "!=", request()->ignore_branch_id);//copied put not in branch
//                    })->orDoesntHave("details");//not copies
//                })
                    $query->whereHas("relatedBranches", function ($query) {
                        $query->where("branch_id", request()->ignore_branch_id);
                    });
            })
            ->where('company_id', $company->id);

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);
        return array_merge($paginationArray, [
            'data' => $paginatedData
        ]);
    }

    /**
     * Get all management hierarchies where detail.is_copied = 0 with detail.managementHierarchy relationship (without pagination)
     *
     * @return Collection
     */
    public function getAllNonCopiedHierarchies(): Collection
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();

        $filters = request()->all();
        if (!array_key_exists('type', $filters)) {
            $filters["type"] = "management";
        }

        return SourceManagementHierarchy::query()->with(['detail.managementHierarchy', 'company'])
            ->where('company_id', $company->id)->filter($filters)
            ->get();
    }

    public function createSourceManagementHierarchy($data): SourceManagementHierarchy
    {
        return SourceManagementHierarchy::create($data);
    }

    /**
     * Create management with related job types, job titles, and branches
     */
    public function createManagementWithRelations(
        array  $managementData,
        array  $managementDetail,
        ?array $deputyManagers,
        array  $jobTypes = [],
        array  $jobTitles = [],
        array  $branches = []
    ): SourceManagementHierarchy
    {
//        try {
        DB::beginTransaction();

        $mainBranch = $this->findOneBy([
            "company_id" => $managementData["company_id"],
            "parent_id" => null,
            "type" => "branch",
            "is_main" => 1
        ]);

        $parentId = $managementData["parent_id"] ?? null;


        // Create the management hierarchy
        $relatedSourceManagementHierarchy = SourceManagementHierarchy::query()->where('id', $managementData['parent_id'])->first();
        $detail = ManagementHierarchyDetail::where(['reference_department_id' => $relatedSourceManagementHierarchy->id, "branch_id" => $mainBranch->id])->first();

        $managementData['parent_id'] = $detail->management_hierarchy_id;//convert parent_id from source to management
        $managementDetail['branch_id'] = $mainBranch->id;


        $sourceManagementHierarchy = $this->createSourceManagementHierarchy(["name" => $managementData["name"], "type" => $managementData["type"], "company_id" => $managementData["company_id"] , "parent_id" => $parentId]);


//        $managementHierarchy = $this->createManagement($managementData, $managementDetail + ["reference_department_id" => $sourceManagementHierarchy->id, "is_copied" => 1], $deputyManagers);


        // Sync job types
        if (!empty($jobTypes)) {
            $sourceManagementHierarchy->jobTypes()->sync($jobTypes);
        }

        // Sync job titles
        if (!empty($jobTitles)) {
            $sourceManagementHierarchy->jobTitles()->sync($jobTitles);
        }

        // Sync related branches
        if (!empty($branches)) {
            $sourceManagementHierarchy->relatedBranches()->sync($branches);
        }

        DB::commit();

        // Load relationships for response
        $sourceManagementHierarchy->load(['jobTypes', 'jobTitles', 'relatedBranches', 'details']);

//        } catch (\Exception $e) {
//            DB::rollBack();
//            throw new CustomException($e->getMessage(), 500);
//        }

        return $sourceManagementHierarchy;
    }


    /**
     * Create management with related job types, job titles, and branches
     */
    public function createDepartmentWithRelations(
        array  $departmentData,
        array  $departmentDetail,
        ?array $deputyManagers,//not use put for future if ask
        array  $managements = []
    ): SourceManagementHierarchy
    {
        try {
            DB::beginTransaction();

            // Create the management hierarchy
//            $managementHierarchy = $this->createDepartment($departmentData, $departmentDetail, $deputyManagers);
            $sourceManagementHierarchy = $this->createSourceManagementHierarchy(["name" => $departmentData["name"], "type" => $departmentData["type"], "company_id" => $departmentData["company_id"]]);


            // Sync managements
            if (!empty($managements)) {
                $sourceManagementHierarchy->relatedManagements()->sync($managements);
            }

            DB::commit();

            // Load relationships for response
            $sourceManagementHierarchy->load(['relatedManagements', 'details']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw new CustomException($e->getMessage(), 500);
        }

        return $sourceManagementHierarchy;
    }

    /**
     * Update management with related job types, job titles, and branches
     */
    public function updateManagementWithRelations(
        int    $managementId,
        array  $managementData,
        array  $managementDetail,
        ?array $deputyManagers,
        array  $jobTypes = [],
        array  $jobTitles = [],
        array  $branches = []
    ): SourceManagementHierarchy
    {
        try {
            DB::beginTransaction();

            // Find the existing source management hierarchy
            $sourceManagementHierarchy = SourceManagementHierarchy::findOrFail($managementId);

            // Update the source management hierarchy
            $sourceManagementHierarchy->update([
                'name' => $managementData['name'],
                'type' => $managementData['type'],
                'company_id' => $managementData['company_id'],
                "parent_id" => $managementData["parent_id"]
            ]);

            // Find the management hierarchy detail
            $details = ManagementHierarchyDetail::where('reference_department_id', $sourceManagementHierarchy->id)->get();

            foreach ($details as $detail) {
                // Update the management hierarchy
                $managementHierarchy = ManagementHierarchy::findOrFail($detail->management_hierarchy_id);

                // Handle parent_id conversion if needed
//                if (isset($managementData['parent_id']) && $managementData['parent_id']) {
//                    $relatedSourceManagementHierarchy = SourceManagementHierarchy::query()->where('id', $managementData['parent_id'])->first();
//                    $parentDetail = ManagementHierarchyDetail::where(['reference_department_id' => $relatedSourceManagementHierarchy->id, "branch_id" => $detail->branch_id])->first();
//                    $managementData['parent_id'] = $parentDetail ? $parentDetail->management_hierarchy_id : null;
//                }

                $managementHierarchy->update(["name" => $managementData["name"]]);

                // Update the detail
                $detail->update($managementDetail);

                // Update deputy managers if provided
                if ($deputyManagers !== null) {
                    // Remove existing deputy managers
                    ManagementHierarchyDetailManager::where('management_hierarchy_detail_id', $detail->id)->delete();

                    // Add new deputy managers
                    foreach ($deputyManagers as $deputyManagerId) {
                        ManagementHierarchyDetailManager::create([
                            'management_hierarchy_detail_id' => $detail->id,
                            'user_id' => $deputyManagerId
                        ]);
                    }
                }
            }


            // Sync job types
            $sourceManagementHierarchy->jobTypes()->sync($jobTypes);

            // Sync job titles
            $sourceManagementHierarchy->jobTitles()->sync($jobTitles);

            // Sync related branches
            $sourceManagementHierarchy->relatedBranches()->sync($branches);

            DB::commit();

            // Load relationships for response
            $sourceManagementHierarchy->load(['jobTypes', 'jobTitles', 'relatedBranches', 'details']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw new CustomException($e->getMessage(), 500);
        }

        return $sourceManagementHierarchy;
    }

    /**
     * Update department with related managements
     */
    public function updateDepartmentWithRelations(
        int   $departmentId,
        array $departmentData,
        array $departmentDetail,
        array $managements = []
    ): SourceManagementHierarchy
    {
        try {
            DB::beginTransaction();

            // Find the existing source management hierarchy
            $sourceManagementHierarchy = SourceManagementHierarchy::findOrFail($departmentId);

            // Update the source management hierarchy
            $sourceManagementHierarchy->update([
                'name' => $departmentData['name'],
                'type' => $departmentData['type'],
                'company_id' => $departmentData['company_id']
            ]);

            // Sync managements
            $sourceManagementHierarchy->relatedManagements()->sync($managements);

            DB::commit();

            // Load relationships for response
            $sourceManagementHierarchy->load(['relatedManagements', 'details']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw new CustomException($e->getMessage(), 500);
        }

        return $sourceManagementHierarchy;
    }

    /**
     * Delete management with all related data (job types, job titles, branches, deputy managers)
     */
    public function deleteManagementWithRelations(int $managementId): bool
    {
        try {
            DB::beginTransaction();

            // Find the existing source management hierarchy
            $sourceManagementHierarchy = SourceManagementHierarchy::findOrFail($managementId);

            // Check if management has children before deleting
            if ($sourceManagementHierarchy->details()->count() > 0) {
                throw new CustomException('Cannot delete management hierarchy that has children.', 422);
            }

            // Detach all related job types
            $sourceManagementHierarchy->jobTypes()->detach();

            // Detach all related job titles
            $sourceManagementHierarchy->jobTitles()->detach();

            // Detach all related branches
            $sourceManagementHierarchy->relatedBranches()->detach();

//            // Get the management hierarchy to delete deputy managers
//            $managementHierarchy = $sourceManagementHierarchy->managementHierarchy;
//            if ($managementHierarchy && $managementHierarchy->detail) {
//                // Delete deputy managers
//                $this->deleteDeputyManagers($managementHierarchy->detail->id);
//
//                // Delete the management hierarchy detail
//                $managementHierarchy->detail->delete();
//
//                // Delete the management hierarchy
//                $managementHierarchy->delete();
//            }

            // Delete the source management hierarchy
            $sourceManagementHierarchy->delete();

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new CustomException($e->getMessage(), 500);
        }
    }

    /**
     * Delete department with all related managements
     */
    public function deleteDepartmentWithRelations(int $departmentId): bool
    {
        try {
            DB::beginTransaction();

            // Find the existing source management hierarchy
            $sourceManagementHierarchy = SourceManagementHierarchy::findOrFail($departmentId);

            // Check if department has children before deleting
            if ($sourceManagementHierarchy->details()->count() > 0) {
                throw new CustomException('Cannot delete department hierarchy that has children.', 422);
            }

            // Detach all related managements
            $sourceManagementHierarchy->relatedManagements()->detach();

            // Delete the source management hierarchy
            $sourceManagementHierarchy->delete();

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new CustomException($e->getMessage(), 500);
        }
    }

    /**
     * Get branches for lookup (type = 'branch')
     */
    public function getBranchesLookup(): Collection
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();

        return $this->model
            ->where('type', 'branch')
            ->where('company_id', $company->id)
            ->where('is_active', 1)
            ->select(['id', 'name'])
            ->get();
    }

    public function findNonCopiedHierarchyById( $id): ?SourceManagementHierarchy
    {
        return SourceManagementHierarchy::query()
            ->with([
                'details.managementHierarchy',
                'company',
                'jobTypes',
                'jobTitles',
                'relatedBranches',
                "relatedManagements"
            ])
            ->find($id);
    }
}
