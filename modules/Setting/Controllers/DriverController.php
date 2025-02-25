<?php

declare(strict_types=1);

namespace Modules\Setting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

use Modules\Setting\Requests\driver\GetDriverListRequest;

use Modules\Setting\Services\DriverService;


class DriverController extends Controller
{
    public function __construct(
        private DriverService $driverService,
    )
    {
    }

    public function index(GetDriverListRequest $request): JsonResponse
    {
        $list = $this->driverService->all();

        return Json::item($list);
    }


}
