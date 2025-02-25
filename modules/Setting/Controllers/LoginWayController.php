<?php

declare(strict_types=1);

namespace Modules\Setting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Setting\Handlers\DeleteLoginWayHandler;
use Modules\Setting\Handlers\MakeLoginWayDefaultHandler;
use Modules\Setting\Handlers\UpdateLoginWayHandler;
use Modules\Setting\Models\LoginWay;
use Modules\Setting\Models\LoginWayStep;
use Modules\Setting\Presenters\LoginWayPresenter;
use Modules\Setting\Presenters\LoginWayWithSpecificStepPresenter;
use Modules\Setting\Requests\LoginWay\CreateLoginWayRequest;
use Modules\Setting\Requests\LoginWay\DeleteLoginWayRequest;
use Modules\Setting\Requests\LoginWay\GetLoginWayListRequest;
use Modules\Setting\Requests\LoginWay\MakeLoginWayDefaultRequest;
use Modules\Setting\Requests\LoginWay\ShowLoginWayRequest;
use Modules\Setting\Requests\LoginWay\UpdateLoginWayRequest;
use Modules\Setting\Services\LoginWayService;
use Ramsey\Uuid\Uuid;


class LoginWayController extends Controller
{
    public function __construct(
        private LoginWayService       $loginWayService,
        private UpdateLoginWayHandler $loginWayHandler,
        private DeleteLoginWayHandler $deleteHandler,
        private MakeLoginWayDefaultHandler $makeDefaultHandler,

    )
    {
    }

    public function index(GetLoginWayListRequest $request)
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

    public function show(ShowLoginWayRequest $request)
    {
        try {
            $loginWay = $this->loginWayService->getLoginWay(Uuid::fromString($request->route("id")));
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), httpStatus: $e->getCode());
        }


        return Json::item((new LoginWayPresenter($loginWay))->getData());
    }

    public function delete(DeleteLoginWayRequest $request)
    {

        try {
            $this->deleteHandler->handle(Uuid::fromString($request->route('id')));
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), httpStatus: $e->getCode());
        }
        return Json::deleted();
    }

    public function makeLoginWayDefault(MakeLoginWayDefaultRequest $request)
    {
        $this->makeDefaultHandler->handle($request->get("id"));
        return Json::success(  "Login way default successfully");
    }


}
