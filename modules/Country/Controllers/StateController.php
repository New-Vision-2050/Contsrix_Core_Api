<?php

declare(strict_types=1);

namespace Modules\Country\Controllers;

use App\Http\Controllers\Controller;
use AWS\CRT\HTTP\Request;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Country\Handlers\DeleteStateHandler;
use Modules\Country\Handlers\UpdateStateHandler;
use Modules\Country\Presenters\CityPresenter;
use Modules\Country\Presenters\StatePresenter;
use Modules\Country\Requests\CreateStateRequest;
use Modules\Country\Requests\GetCountryAndStateAndCityRequest;
use Modules\Country\Requests\GetStateListRequest;
use Modules\Country\Requests\GetStateRequest;
use Modules\Country\Requests\UpdateStateRequest;
use Modules\Country\Services\StateCRUDService;
use Ramsey\Uuid\Uuid;

class StateController extends Controller
{
    public function __construct(
        private StateCRUDService $StateService,
        private UpdateStateHandler $updateStateHandler,
        private DeleteStateHandler $deleteStateHandler,
    ) {
    }

    public function index(GetStateListRequest $request): JsonResponse
    {
        $list = $this->StateService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(StatePresenter::collection($list['data']),paginationSettings:$list['pagination']);
    }
    public function getCity(GetStateListRequest $request): JsonResponse
    {
        $list = $this->StateService->listCity(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(CityPresenter::collection($list['data']),paginationSettings:$list['pagination']);
    }



    public function show(GetStateRequest $request): JsonResponse
    {
        $item = $this->StateService->get(Uuid::fromString($request->route('id')));

        $presenter = new StatePresenter($item);

        return Json::item('State', $presenter->getData());
    }

    public function store(CreateStateRequest $request): JsonResponse
    {
        $createdItem = $this->StateService->create($request->createCreateStateDTO());

        $presenter = new StatePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateStateRequest $request): JsonResponse
    {
        $command = $request->createUpdateStateCommand();
        $this->updateStateHandler->handle($command);

        $item = $this->StateService->get($command->getId());

        $presenter = new StatePresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteStateRequest $request): JsonResponse
    {
        $this->deleteStateHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }


    public function getStateWithStateWithCity( GetStateAndStateAndCityRequest $request)
    {
        $data = $this->StateService->getStateWithStateWithCity();
        return Json::item(MixedPresenter::collection($data));

    }

    public function currency(GetStateListRequest $request): JsonResponse
    {
        $StateId = $request->get('State_id');
        $list = $this->StateService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(StateCurrencyPresenter::collection($list['data']),paginationSettings:$list['pagination']);
    }

    public function getStatesByCurrentAuthUserBranch()
    {
        $states =  $this->StateService->getStatesByStateBranch();
        return Json::items(MixedPresenter::collection($states));
    }

}
