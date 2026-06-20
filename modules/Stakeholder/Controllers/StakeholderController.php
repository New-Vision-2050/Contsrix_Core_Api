<?php

declare(strict_types=1);

namespace Modules\Stakeholder\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Stakeholder\Presenters\StakeholderPresenter;
use Modules\Stakeholder\Requests\CreateStakeholderRequest;
use Modules\Stakeholder\Requests\DeleteStakeholderRequest;
use Modules\Stakeholder\Requests\GetStakeholderListRequest;
use Modules\Stakeholder\Requests\GetStakeholderRequest;
use Modules\Stakeholder\Requests\UpdateStakeholderRequest;
use Modules\Stakeholder\Services\StakeholderCRUDService;
use Ramsey\Uuid\Uuid;

class StakeholderController extends Controller
{
    public function __construct(
        private StakeholderCRUDService $service,
    ) {
    }

    public function index(GetStakeholderListRequest $request): JsonResponse
    {
        $list = $this->service->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(
            StakeholderPresenter::collection($list['data']),
            paginationSettings: $list['pagination']
        );
    }

    public function show(GetStakeholderRequest $request): JsonResponse
    {
        $item = $this->service->get(Uuid::fromString($request->route('id')));
        $presenter = new StakeholderPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateStakeholderRequest $request): JsonResponse
    {
        $createdItem = $this->service->create($request->createDTO());
        $presenter = new StakeholderPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateStakeholderRequest $request): JsonResponse
    {
        $id = Uuid::fromString($request->route('id'));
        $this->service->update($id, $request->validated());
        $item = $this->service->get($id);
        $presenter = new StakeholderPresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteStakeholderRequest $request): JsonResponse
    {
        $this->service->delete(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
