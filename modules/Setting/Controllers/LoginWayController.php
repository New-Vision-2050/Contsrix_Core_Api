<?php

declare(strict_types=1);

namespace Modules\Setting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Setting\DTO\CreateLoginWayDTO;
use Modules\Setting\Handlers\UpdateLoginWayHandler;
use Modules\Setting\Presenters\LoginWayPresenter;
use Modules\Setting\Presenters\SettingPresenter;
use Modules\Setting\Requests\LoginWay\CreateLoginWayRequest;
use Modules\Setting\Requests\LoginWay\GetLoginWayListRequest;
use Modules\Setting\Requests\LoginWay\UpdateLoginWayRequest;
use Modules\Setting\Services\LoginWayService;


class LoginWayController extends Controller
{
    public function __construct(
        private LoginWayService       $loginWayService,
        private UpdateLoginWayHandler $loginWayHandler
    )
    {
    }

    public function index(GetLoginWayListRequest $request): JsonResponse
    {
        $list = $this->loginWayService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::item(["login_way" => LoginWayPresenter::collection($list["data"]), "pagination" => $list["pagination"]]);
    }

    public function store(CreateLoginWayRequest $request)
    {
        $loginWay = $this->loginWayService->create($request->createCreateLoginWayDTO());

        return Json::item((new LoginWayPresenter($loginWay))->getData(), message: "Login way created successfully");
    }

    public function update(UpdateLoginWayRequest $request)
    {
        $command = $request->createUpdateLoginWayCommand();
        $this->loginWayHandler->handle($command);
        $loginWay = $this->loginWayService->getLoginWay($command->getId());

        return Json::item((new LoginWayPresenter($loginWay))->getData(), message: "Login way Updated successfully");
    }


}
