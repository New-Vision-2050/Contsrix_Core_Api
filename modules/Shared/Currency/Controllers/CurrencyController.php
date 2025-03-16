<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\Currency\Handlers\DeleteCurrencyHandler;
use Modules\Shared\Currency\Handlers\UpdateCurrencyHandler;
use Modules\Shared\Currency\Presenters\CurrencyPresenter;
use Modules\Shared\Currency\Requests\CreateCurrencyRequest;
use Modules\Shared\Currency\Requests\DeleteCurrencyRequest;
use Modules\Shared\Currency\Requests\GetCurrencyListRequest;
use Modules\Shared\Currency\Requests\GetCurrencyRequest;
use Modules\Shared\Currency\Requests\UpdateCurrencyRequest;
use Modules\Shared\Currency\Services\CurrencyCRUDService;
use Ramsey\Uuid\Uuid;

class CurrencyController extends Controller
{
    public function __construct(
        private CurrencyCRUDService $currencyService,
        private UpdateCurrencyHandler $updateCurrencyHandler,
        private DeleteCurrencyHandler $deleteCurrencyHandler,
    ) {
    }

    public function index(GetCurrencyListRequest $request): JsonResponse
    {
        $list = $this->currencyService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(CurrencyPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetCurrencyRequest $request): JsonResponse
    {
        $item = $this->currencyService->get(Uuid::fromString($request->route('id')));

        $presenter = new CurrencyPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateCurrencyRequest $request): JsonResponse
    {
        $createdItem = $this->currencyService->create($request->createCreateCurrencyDTO());

        $presenter = new CurrencyPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateCurrencyRequest $request): JsonResponse
    {
        $command = $request->createUpdateCurrencyCommand();
        $this->updateCurrencyHandler->handle($command);

        $item = $this->currencyService->get($command->getId());

        $presenter = new CurrencyPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteCurrencyRequest $request): JsonResponse
    {
        $this->deleteCurrencyHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
