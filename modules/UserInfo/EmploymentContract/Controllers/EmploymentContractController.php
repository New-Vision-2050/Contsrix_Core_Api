<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\EmploymentContract\Handlers\DeleteEmploymentContractHandler;
use Modules\UserInfo\EmploymentContract\Handlers\UpdateEmploymentContractHandler;
use Modules\UserInfo\EmploymentContract\Presenters\EmploymentContractPresenter;
use Modules\UserInfo\EmploymentContract\Requests\CreateEmploymentContractRequest;
use Modules\UserInfo\EmploymentContract\Requests\DeleteEmploymentContractRequest;
use Modules\UserInfo\EmploymentContract\Requests\GetEmploymentContractListRequest;
use Modules\UserInfo\EmploymentContract\Requests\GetEmploymentContractRequest;
use Modules\UserInfo\EmploymentContract\Requests\UpdateEmploymentContractRequest;
use Modules\UserInfo\EmploymentContract\Services\EmploymentContractCRUDService;
use Ramsey\Uuid\Uuid;

class EmploymentContractController extends Controller
{
    public function __construct(
        private EmploymentContractCRUDService $employmentContractService,
        private UserRepository $userRepository
    ) {
    }

    public function index(GetEmploymentContractListRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));

        $user = $this->userRepository->getUser($userId);
        
        $item = $this->employmentContractService->get(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
        );

        $presenter = new EmploymentContractPresenter($item);

        return Json::item($presenter->getData());
    }

    public function show(GetEmploymentContractRequest $request): JsonResponse
    {
        $item = $this->employmentContractService->get(Uuid::fromString($request->route('id')));

        $presenter = new EmploymentContractPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEmploymentContractRequest $request): JsonResponse
    {
        $createCreateEmploymentContractDTO = $request->createCreateEmploymentContractDTO();
        $userId = Uuid::fromString($request->input('user_id'));

        $user = $this->userRepository->getUser($userId);
        $createCreateEmploymentContractDTO->global_id = $user->global_company_user_id;
        $createCreateEmploymentContractDTO->company_id = $user->company_id;

        $createdItem = $this->employmentContractService->create($createCreateEmploymentContractDTO);

        $presenter = new EmploymentContractPresenter($createdItem);

        return Json::item($presenter->getData());
    }
}
