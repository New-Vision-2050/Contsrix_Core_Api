<?php

declare(strict_types=1);

namespace Modules\Setting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

use Modules\Setting\Handlers\UpdateDriverHandler;
use Modules\Setting\Requests\driver\GetDriverListRequest;

use Modules\Setting\Requests\driver\UpdateDriverRequest;
use Modules\Setting\Services\DriverService;


class DriverController extends Controller
{
    public function __construct(
        private DriverService $driverService,
        private UpdateDriverHandler $updateDriverHandler
    )
    {
    }

    public function index(GetDriverListRequest $request): JsonResponse
    {
        $list = $this->driverService->all();

        return Json::item($list);
    }

    public function updateDriver(UpdateDriverRequest $request): JsonResponse
    {
        try {
            $command = $request->createUpdateDriverCommand();
        }
        catch (\Exception $e) {
            return Json::error($e->getMessage());
        }

        $this->updateDriverHandler->handle($command);

        $driver = $this->driverService->show($command->getId());

        return Json::item(["driver"=> $driver]);

    }


}
