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
        private LoginWayService            $loginWayService,
        private UpdateLoginWayHandler      $loginWayHandler,
        private DeleteLoginWayHandler      $deleteHandler,
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

        return Json::items(LoginWayPresenter::collection($list["data"]), $list["pagination"]);
    }

    public function store(CreateLoginWayRequest $request)
    {
        $loginWay = $this->loginWayService->create($request->createCreateLoginWayDTO());

        return Json::item((new LoginWayPresenter($loginWay))->getData(), message: __("validation.create-successful"));
    }

    public function update(UpdateLoginWayRequest $request)
    {
        $command = $request->createUpdateLoginWayCommand();
        $this->loginWayHandler->handle($command);
        $loginWay = $this->loginWayService->getLoginWay($command->getId());

        return Json::item((new LoginWayPresenter($loginWay))->getData(), message: __('validation.update-successful'));
    }

    public function show(ShowLoginWayRequest $request)
    {
        $loginWay = $this->loginWayService->getLoginWay(Uuid::fromString($request->route("id")));

        return Json::item((new LoginWayPresenter($loginWay))->getData());
    }

    public function delete(DeleteLoginWayRequest $request)
    {
        $this->deleteHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function makeLoginWayDefault(MakeLoginWayDefaultRequest $request)
    {
        $this->makeDefaultHandler->handle(Uuid::fromString($request->route("id")));
        return Json::success(__("validation.update-successful"));
    }

    public function loginOptionsWithAllRelatedRelations()
    {
        $loginOptions = $this->loginWayService->loginOptionWithAllRelatedRelations();
        return Json::item($loginOptions);

    }

    public function loginOptions()
    {

        return Json::item($this->loginWayService->loginOptionWithAllRelatedRelations()->pluck("login_option"));

    }


    public function getDriversByLoginOption(ShowLoginWayRequest $request)
    {

        return Json::item($this->loginWayService->getDriversByLoginOption($request->route("loginOption")));
    }
    public function getAlternativesByLoginOption(ShowLoginWayRequest $request)
    {

        return Json::item($this->loginWayService->getAlternativeDriversByLoginOption($request->route("loginOption"),$request->route("driver")));
    }


}
