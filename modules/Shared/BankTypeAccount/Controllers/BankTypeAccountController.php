<?php

declare(strict_types=1);

namespace Modules\Shared\BankTypeAccount\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\BankTypeAccount\Handlers\DeleteBankTypeAccountHandler;
use Modules\Shared\BankTypeAccount\Handlers\UpdateBankTypeAccountHandler;
use Modules\Shared\BankTypeAccount\Presenters\BankTypeAccountPresenter;
use Modules\Shared\BankTypeAccount\Requests\CreateBankTypeAccountRequest;
use Modules\Shared\BankTypeAccount\Requests\DeleteBankTypeAccountRequest;
use Modules\Shared\BankTypeAccount\Requests\GetBankTypeAccountListRequest;
use Modules\Shared\BankTypeAccount\Requests\GetBankTypeAccountRequest;
use Modules\Shared\BankTypeAccount\Requests\UpdateBankTypeAccountRequest;
use Modules\Shared\BankTypeAccount\Services\BankTypeAccountCRUDService;
use Ramsey\Uuid\Uuid;

class BankTypeAccountController extends Controller
{
    public function __construct(
        private BankTypeAccountCRUDService $bankTypeAccountService,
        private UpdateBankTypeAccountHandler $updateBankTypeAccountHandler,
        private DeleteBankTypeAccountHandler $deleteBankTypeAccountHandler,
    ) {
    }

    public function index(GetBankTypeAccountListRequest $request): JsonResponse
    {
        $list = $this->bankTypeAccountService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(BankTypeAccountPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetBankTypeAccountRequest $request): JsonResponse
    {
        $item = $this->bankTypeAccountService->get(Uuid::fromString($request->route('id')));

        $presenter = new BankTypeAccountPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateBankTypeAccountRequest $request): JsonResponse
    {
        $createdItem = $this->bankTypeAccountService->create($request->createCreateBankTypeAccountDTO());

        $presenter = new BankTypeAccountPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateBankTypeAccountRequest $request): JsonResponse
    {
        $command = $request->createUpdateBankTypeAccountCommand();
        $this->updateBankTypeAccountHandler->handle($command);

        $item = $this->bankTypeAccountService->get($command->getId());

        $presenter = new BankTypeAccountPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteBankTypeAccountRequest $request): JsonResponse
    {
        $this->deleteBankTypeAccountHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
