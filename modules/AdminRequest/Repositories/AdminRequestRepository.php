<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AdminRequest\Enum\AdminRequestStatus;
use Modules\Company\CompanyCore\Models\Company;
use Ramsey\Uuid\UuidInterface;
use Modules\AdminRequest\Models\AdminRequest;

/**
 * @property AdminRequest $model
 * @method AdminRequest findOneOrFail($id)
 * @method AdminRequest findOneByOrFail(array $data)
 */
class AdminRequestRepository extends BaseRepository
{
    public function __construct(AdminRequest $model)
    {
        parent::__construct($model);
    }

    public function getAdminRequestList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getAdminRequest(UuidInterface $id): AdminRequest
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    /**
     * @param UuidInterface $userId
     * @param array $data
     * @param string $requestType
     * @param array $action
     * @return AdminRequest
     */

    public function createAdminRequestForCompanyOfficialData(UuidInterface $userId, array $data, string $requestType, array $action,?string $notes=""): AdminRequest
    {
        $id = $data['id'];
        unset($data["id"]);
        try {
            DB::beginTransaction();
            $adminRequest = $this->create([
                'user_id' => $userId,
                'request_type' => $requestType,
                'action' => $action,
                'data' => $data,
                "requestable_id" => $id,
                "requestable_type" => Company::class,
                "notes" => $notes
            ]);
            $adminRequest->adminRequestTransactions()->create([
                "data" => $data,
                "action" => "update",
                "requestable_id" => $id,
                "requestable_type" => Company::class,
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 409);
        }
        return $adminRequest;
    }


    public function createAdminRequestForCompanyLegalData(UuidInterface $userId, array $data, string $requestType, array $action,?string $notes=""): AdminRequest
    {
        $id = $data['id'];
        unset($data["id"]);
        try {
            DB::beginTransaction();
            $adminRequest = $this->create([
                'user_id' => $userId,
                'request_type' => $requestType,
                'action' => $action,
                'data' =>$data,
                "requestable_id" => $id,
                "requestable_type" => Company::class,
                "notes" => $notes
            ]);
            $adminRequest->adminRequestTransactions()->create([
                "data" => $data,
                "action" => "update",
                "requestable_id" => $id,
                "requestable_type" => Company::class,
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 409);
        }
        return $adminRequest;
    }

    public function acceptActionOnAdminRequest( UuidInterface $id , $status): AdminRequest
    {
        try {
           $adminRequest =  $this->findOneByOrFail(["id"=>$id]);

        }catch (\Exception $e) {
            throw new \Exception(__("validation.not-found"), 404);
        }
        try {
            DB::beginTransaction();
            foreach ($adminRequest->adminRequestTransactions as $adminRequestTransaction){
                if($adminRequestTransaction->action == "update"){
                    $model  = new $adminRequestTransaction->requestable_type;
                    $model->find($adminRequestTransaction->requestable_id)->update($adminRequestTransaction->data);

                }
                $adminRequestTransaction->update(["status"=>$status]);
            }
            $adminRequest->update(["status"=>$status]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.create-not-successful"), 500);
        }

        return $adminRequest->fresh();
    }

    public function rejectActionOnAdminRequest( UuidInterface $id , $status): AdminRequest
    {
        try {
           $adminRequest =  $this->findOneByOrFail(["id"=>$id]);

        }catch (\Exception $e) {
            throw new \Exception(__("validation.not-found"), 404);
        }
        try {
            DB::beginTransaction();
            foreach ($adminRequest->adminRequestTransactions as $adminRequestTransaction){

                $adminRequestTransaction->update(["status"=>$status]);
            }
            $adminRequest->update(["status"=>$status]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.create-not-successful"), 500);
        }

        return $adminRequest->fresh();
    }

    public function setAddress(UuidInterface $id ,array $array)
    {

    }


    public function updateAdminRequest(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteAdminRequest(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
