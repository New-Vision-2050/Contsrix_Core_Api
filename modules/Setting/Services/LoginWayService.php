<?php

declare(strict_types=1);

namespace Modules\Setting\Services;

use Modules\Setting\DTO\CreateLoginWayDTO;
use Modules\Setting\DTO\CreateSettingDTO;
use Modules\Setting\Models\LoginWay;
use Modules\Setting\Models\Setting;
use Modules\Setting\Presenters\DriverPresenter;
use Modules\Setting\Presenters\LoginOptionLookupPresenter;
use Modules\Setting\Presenters\LoginOptionPresenter;
use Modules\Setting\Repositories\DriverRepository;
use Modules\Setting\Repositories\LoginWayRepository;
use Modules\Setting\Repositories\SettingRepository;
use Ramsey\Uuid\UuidInterface;
use function Laravel\Prompts\password;

class LoginWayService
{
    public function __construct(
        private LoginWayRepository $repository,
        private DriverRepository   $driverRepository,
    )
    {
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
        $loginWay = $this->repository->findOneBy(['id' => $id]);
        if (!$loginWay) {
            throw new \DomainException(__("validation.login-way-not-found"), 404);

        }
        return $loginWay;
    }

    public function loginOption()
    {
        $driverTypesData = [];
        $driverTypes = $this->driverRepository->getDataGroupByType()->keys()->toArray();
        $alternatives = $driverTypes+["password"];
        foreach ($driverTypes as $type) {
            $driverTypesData[] = [
                'key' => $type,
                "alternatives" => array_filter($alternatives, function($item) use ($type) {
                    return $item !== $type;
                })

            ];
        }

        return [
            'login_options' => [
                [
                    'login_option' => 'password',
                    'driver_types' => null
                ],
                [
                    'login_option' => 'otp',
                    'driver_types' => $driverTypesData
                ]
            ]
        ];
    }
}
