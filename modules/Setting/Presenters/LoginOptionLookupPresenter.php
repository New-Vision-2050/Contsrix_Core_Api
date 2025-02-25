<?php

declare(strict_types=1);

namespace Modules\Setting\Presenters;

use Modules\Setting\Models\LoginWayStep;
use Modules\Setting\Models\Setting;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Setting\Repositories\DriverRepository;

class LoginOptionLookupPresenter extends AbstractPresenter
{

    private DriverRepository $driverRepository;

    public function __construct(public array $loginOption)
    {
        $this->driverRepository = app(DriverRepository::class);
    }

    protected function present(bool $isListing = false): array
    {
        $driverTypes = [];
        foreach ($this->loginOption["driver_types"] as $driverType) {
                $driverTypes[] = [
                    'key' => $driverType,
                    'drivers' =>DriverPresenter::collection($this->driverRepository->findBy(["driver_type" => $driverType])),
                ];


        }

        return [
            'login_option' => $this->loginOption["login_option"],
            'driver_types' => $driverTypes,
        ];
    }
}
