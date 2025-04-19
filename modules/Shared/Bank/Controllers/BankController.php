<?php

declare(strict_types=1);

namespace Modules\Shared\Bank\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\Bank\Handlers\DeleteBankHandler;
use Modules\Shared\Bank\Handlers\UpdateBankHandler;
use Modules\Shared\Bank\Presenters\BankPresenter;
use Modules\Shared\Bank\Requests\CreateBankRequest;
use Modules\Shared\Bank\Requests\DeleteBankRequest;
use Modules\Shared\Bank\Requests\GetBankListRequest;
use Modules\Shared\Bank\Requests\GetBankRequest;
use Modules\Shared\Bank\Requests\UpdateBankRequest;
use Modules\Shared\Bank\Services\BankCRUDService;
use Ramsey\Uuid\Uuid;

class BankController extends Controller
{
    public function __construct(
        private BankCRUDService $bankService,
        private UpdateBankHandler $updateBankHandler,
        private DeleteBankHandler $deleteBankHandler,
    ) {
    }

    public function index(GetBankListRequest $request): JsonResponse
    {
        $list = $this->bankService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(BankPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetBankRequest $request): JsonResponse
    {
        $item = $this->bankService->get(Uuid::fromString($request->route('id')));

        $presenter = new BankPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateBankRequest $request): JsonResponse
    {
        $createdItem = $this->bankService->create($request->createCreateBankDTO());

        $presenter = new BankPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateBankRequest $request): JsonResponse
    {
        $command = $request->createUpdateBankCommand();
        $this->updateBankHandler->handle($command);

        $item = $this->bankService->get($command->getId());

        $presenter = new BankPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteBankRequest $request): JsonResponse
    {
        $this->deleteBankHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
