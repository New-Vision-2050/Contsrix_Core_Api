<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\BankAccount\Handlers\DeleteBankAccountHandler;
use Modules\UserInfo\BankAccount\Handlers\UpdateBankAccountHandler;
use Modules\UserInfo\BankAccount\Handlers\UpdateTypeBankAccountHandler;
use Modules\UserInfo\BankAccount\Presenters\BankAccountPresenter;
use Modules\UserInfo\BankAccount\Requests\CreateBankAccountRequest;
use Modules\UserInfo\BankAccount\Requests\DeleteBankAccountRequest;
use Modules\UserInfo\BankAccount\Requests\GetBankAccountListRequest;
use Modules\UserInfo\BankAccount\Requests\GetBankAccountRequest;
use Modules\UserInfo\BankAccount\Requests\UpdateBankAccountRequest;
use Modules\UserInfo\BankAccount\Requests\UpdateTypeBankAccountRequest;
use Modules\UserInfo\BankAccount\Services\BankAccountCRUDService;
use Ramsey\Uuid\Uuid;

class BankAccountController extends Controller
{
    public function __construct(
        private BankAccountCRUDService $bankAccountService,
        private UpdateBankAccountHandler $updateBankAccountHandler,
        private UpdateTypeBankAccountHandler $updateTypeBankAccountHandler,
        private DeleteBankAccountHandler $deleteBankAccountHandler,
        private CompanyUserCRUDService $companyUserCRUDService,
        private UserRepository $userRepository
    ) {
    }

    public function index(GetBankAccountListRequest $request)//: JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));

        $user = $this->userRepository->getUser($userId);

         $list = $this->bankAccountService->list(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(BankAccountPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetBankAccountRequest $request): JsonResponse
    {
        $item = $this->bankAccountService->get(Uuid::fromString($request->route('id')));

        $presenter = new BankAccountPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateBankAccountRequest $request)//: JsonResponse
    {
        $createCreateBankAccountDTO = $request->createCreateBankAccountDTO();
        $userId = Uuid::fromString($request->input('user_id'));

        $user = $this->userRepository->getUser($userId);
        $createCreateBankAccountDTO->company_id = $user->company_id;
        $createCreateBankAccountDTO->global_id = $user->global_company_user_id;

        $createdItem = $this->bankAccountService->create($createCreateBankAccountDTO);

        $presenter = new BankAccountPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateBankAccountRequest $request): JsonResponse
    {
        $command = $request->createUpdateBankAccountCommand();
        $this->updateBankAccountHandler->handle($command);

        $item = $this->bankAccountService->get($command->getId());

        $presenter = new BankAccountPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function updateType(UpdateTypeBankAccountRequest $request): JsonResponse
    {
        $command = $request->createUpdateTypeBankAccountCommand();
        $this->updateTypeBankAccountHandler->handle($command);

        $item = $this->bankAccountService->get($command->getId());

        $presenter = new BankAccountPresenter($item);

        return Json::item( $presenter->getData());
    }


    public function delete(DeleteBankAccountRequest $request): JsonResponse
    {
        $this->deleteBankAccountHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
