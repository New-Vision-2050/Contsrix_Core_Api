<?php

declare(strict_types=1);

namespace Modules\Setting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Setting\Models\LoginWay;
use Ramsey\Uuid\UuidInterface;

class LoginWayRepository extends BaseRepository
{
    public function __construct(LoginWay $model)
    {
        parent::__construct($model);
    }

    public function getLoginWayList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getLoginWay(string $key): LoginWay
    {
        return $this->findOneBy(['key' => $key]);
    }

    public function createLoginWay(array $data): LoginWay
    {
        try {
            DB::beginTransaction();
            $loginWay= $this->create(["company_id" => $data["company_id"], "name" => $data["name"]]);
            $i = 1;
            foreach ($data["login_options"] as $loginOption) {
                $step = $loginWay->loginWaySteps()->create(["login_option"=>$loginOption["login_option"],"order"=>$i]);
                if(isset($loginOption["driver_ids"]))
                {
                    $step->drivers()->attach($loginOption["driver_ids"]);
                    $i++;
                }
            }
            DB::commit();

        }catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.create-not-successful"),500);
        }
        return $loginWay->fresh();
    }

    public function updateLoginWay(UuidInterface $id ,array $data): LoginWay
    {
        try {
            DB::beginTransaction();
            $loginWay = $this->findOneBy(['id' => $id]);
            $loginWay->update(["company_id" => $data["company_id"], "name" => $data["name"]]);
            $loginWay->loginWaySteps()->each(function ($step) {
                $step->drivers()->detach();
                $step->delete();
            });
            $i = 1;
            foreach ($data["login_options"] as $loginOption) {
                $step = $loginWay->loginWaySteps()->create(["login_option"=>$loginOption["login_option"],"order"=>$i]);
                if(isset($loginOption["driver_ids"]))
                {
                    $step->drivers()->attach($loginOption["driver_ids"]);
                    $i++;
                }
            }
            DB::commit();

        }catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.update-not-successful"),500);
        }
        return $loginWay->fresh();
    }


    public function deleteLoginWay(string $key): bool
    {
        return $this->findOneBy(['key' => $key])->delete();
    }
}
