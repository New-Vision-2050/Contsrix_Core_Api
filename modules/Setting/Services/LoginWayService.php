<?php

declare(strict_types=1);

namespace Modules\Setting\Services;

use Modules\Setting\DTO\CreateLoginWayDTO;
use Modules\Setting\DTO\CreateSettingDTO;
use Modules\Setting\Models\LoginWay;
use Modules\Setting\Models\Setting;
use Modules\Setting\Repositories\LoginWayRepository;
use Modules\Setting\Repositories\SettingRepository;
use Ramsey\Uuid\UuidInterface;

class LoginWayService
{
    public function __construct(
        private LoginWayRepository $repository,
    ) {
    }

    public function create(CreateLoginWayDTO $createLoginWayDTO): LoginWay
    {
         return $this->repository->createLoginWay($createLoginWayDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function all(): Setting
    {
        return $this->repository->all();
    }

    public function getLoginWay(UuidInterface $id)
    {
        return $this->repository->findOneBy(['id'=>$id]);


    }public function getLoginWayBycompanyId(UuidInterface $id)
    {
        return $this->repository->findOneBy(['company_id'=>$id,"default"=>1]);
    }
}
