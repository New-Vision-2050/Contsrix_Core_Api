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
            $loginWay = $this->create(["name" => $data["name"]]);
            $i = 1;
            foreach ($data["login_options"] as $loginOption) {
                $drivers = null;
                $loginOptionAlternatives = null;
                if (isset($loginOption["drivers"])) {
                    $drivers = $loginOption["drivers"];
                }
                if (isset($loginOption["login_option_alternatives"])) {
                    $loginOptionAlternatives = $loginOption["login_option_alternatives"];
                }
                $loginWay->loginWaySteps()->create(["login_option" => $loginOption["login_option"], "drivers" => $drivers, "login_option_alternatives" => $loginOptionAlternatives, "order" => $i]);
                $i++;

            }
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.create-not-successful"), 500);
        }
        return $loginWay->fresh();
    }

    public function updateLoginWay(UuidInterface $id, array $data): LoginWay
    {
        try {
            DB::beginTransaction();
        $loginWay = $this->findOneBy(['id' => $id]);
        $loginWay->update(["name" => $data["name"]]);
        $loginWay->loginWaySteps()->delete();
        $i = 1;
        foreach ($data["login_options"] as $loginOption) {
            $drivers = null;
            $loginOptionAlternatives = null;
            if (isset($loginOption["drivers"])) {
                $drivers = $loginOption["drivers"];
            }
            if (isset($loginOption["login_option_alternatives"])) {
                $loginOptionAlternatives = $loginOption["login_option_alternatives"];
            }

            $loginWay->loginWaySteps()->create(["login_option" => $loginOption["login_option"], "drivers" => $drivers, "login_option_alternatives" => $loginOptionAlternatives, "order" => $i]);
            $i++;

        }
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.update-not-successful"), 500);
        }
        return $loginWay->fresh();
    }


    public function deleteLoginWay(UuidInterface $id)
    {
        if ($this->countBy([]) == 1) {
            throw new \Exception(__("validation.delete-not-successful-must-have-one"), 500);
        }
        return $this->findOneBy(['id' => $id])->delete();
    }

    public function makeLoginWayDefault(UuidInterface $id)
    {
        try {
            DB::beginTransaction();
            $this->findOneBy(['id' => $id])->update(['default' => 1]);
            $this->model->where('id', '<>', $id)->update(['default' => 0]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.update-not-successful"), 500);
        }

    }
}
