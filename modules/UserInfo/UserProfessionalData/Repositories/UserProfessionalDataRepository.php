<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;

/**
 * @property UserProfessionalData $model
 * @method UserProfessionalData findOneOrFail($id)
 * @method UserProfessionalData findOneByOrFail(array $data)
 */
class UserProfessionalDataRepository extends BaseRepository
{
    public function __construct(UserProfessionalData $model,private UserRepository $userRepository)
    {
        parent::__construct($model);
    }

    public function getUserProfessionalDataList(UuidInterface $companyId, UuidInterface $globalId, ?int $page, ?int $perPage = 10)
    {
        return $this->paginated(
            ['company_id' => $companyId, 'global_id' => $globalId],
            $page,
            $perPage
        );
    }

    public function getUserProfessionalData(UuidInterface $companyId, UuidInterface $globalId): ?UserProfessionalData
    {
        return $this->model->where([
            'global_id' => $globalId,
            'company_id' => $companyId,
        ])->first();
    }

    public function createUserProfessionalData(array $data): UserProfessionalData
    {
        return $this->create($data);
    }

    public function createOrUpdateUserProfessionalData(array $data): UserProfessionalData
    {

        try {
            DB::beginTransaction();
            $userProfessionalData = $this->model->where([
                'user_id' => $data['user_id'],
            ])->first();
            $user = $this->userRepository->findOneBy(["id"=>$data["user_id"]]);
            $managementHierarchyId = null;

            if($data["management_id"]!=null){
                $managementHierarchyId = $data["management_id"];
            }
            elseif($data["branch_id"]!=null)
            {
                $managementHierarchyId = $data["branch_id"];
            }
            $user->update(["management_hierarchy_id"=>$managementHierarchyId]);

            
            if ($userProfessionalData) {
                $userProfessionalData->update($data);
                DB::commit();

                return $userProfessionalData;
            }
            $userProfessionalData =  $this->model->create($data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.create-not-successful"),500);
        }
        return $userProfessionalData;
    }


    public function updateUserProfessionalData(UuidInterface $id, array $data): bool
    {
        try {
            DB::beginTransaction();
            $user = $this->userRepository->findOneBy(["global_company_user_id"=>$data["global_id"],"company_id"=>$data["company_id"]]);
            $managementHierarchyId = null;
            if($data["department_id"]!=null)
            {
                $managementHierarchyId = $data["department_id"];
            }
            elseif($data["management_id"]!=null){
                $managementHierarchyId = $data["management_id"];
            }
            elseif($data["branch_id"]!=null)
            {
                $managementHierarchyId = $data["branch_id"];
            }
            $user->update(["management_hierarchy_id"=>$managementHierarchyId]);
            $this->update($id, $data);
            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();
            throw new \Exception(__("validation.update-not-successful"),500);
        }
        return true;
    }

    public function deleteUserProfessionalData(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getById(string $id): ?UserProfessionalData
    {
        return UserProfessionalData::with([
            'attendanceConstraint',
            'branch.defaultAttendanceConstraint'
        ])->find($id);
    }

    public function getByGlobalId(string $globalId): ?UserProfessionalData
    {
        return UserProfessionalData::where('global_id', $globalId)
            ->with([
                'attendanceConstraint',
                'branch.defaultAttendanceConstraint'
            ])
        ->first();
    }
}
