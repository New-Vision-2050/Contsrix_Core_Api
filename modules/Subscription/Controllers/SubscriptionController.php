<?php

declare(strict_types=1);

namespace Modules\Subscription\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Subscription\Handlers\DeleteSubscriptionHandler;
use Modules\Subscription\Handlers\UpdateSubscriptionHandler;
use Modules\Subscription\Presenters\SubscriptionPresenter;
use Modules\Subscription\Requests\CreateSubscriptionRequest;
use Modules\Subscription\Requests\DeleteSubscriptionRequest;
use Modules\Subscription\Requests\GetSubscriptionListRequest;
use Modules\Subscription\Requests\GetSubscriptionRequest;
use Modules\Subscription\Requests\UpdateSubscriptionRequest;
use Modules\Subscription\Services\SubscriptionCRUDService;
use Ramsey\Uuid\Uuid;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionCRUDService $subscriptionService,
        private UpdateSubscriptionHandler $updateSubscriptionHandler,
        private DeleteSubscriptionHandler $deleteSubscriptionHandler,
    ) {
    }

    public function index(GetSubscriptionListRequest $request): JsonResponse
    {
        $list = $this->subscriptionService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(SubscriptionPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetSubscriptionRequest $request): JsonResponse
    {
        $item = $this->subscriptionService->get(Uuid::fromString($request->route('id')));

        $presenter = new SubscriptionPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateSubscriptionRequest $request): JsonResponse
    {
        $createdItem = $this->subscriptionService->create($request->createCreateSubscriptionDTO());

        $presenter = new SubscriptionPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateSubscriptionRequest $request): JsonResponse
    {
        $command = $request->createUpdateSubscriptionCommand();
        $this->updateSubscriptionHandler->handle($command);

        $item = $this->subscriptionService->get($command->getId());

        $presenter = new SubscriptionPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteSubscriptionRequest $request): JsonResponse
    {
        $this->deleteSubscriptionHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
