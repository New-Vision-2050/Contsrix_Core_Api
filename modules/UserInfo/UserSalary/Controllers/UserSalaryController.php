<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserSalary\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\UserSalary\Presenters\UserSalaryPresenter;
use Modules\UserInfo\UserSalary\Requests\CreateUserSalaryRequest;
use Modules\UserInfo\UserSalary\Requests\GetUserSalaryRequest;
use Modules\UserInfo\UserSalary\Services\UserSalaryCRUDService;
use Ramsey\Uuid\Uuid;

class UserSalaryController extends Controller
{
    public function __construct(
        private UserSalaryCRUDService $userSalaryService,
        private UserRepository $userRepository
    ) {
    }

    public function index(GetUserSalaryRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));

        $user = $this->userRepository->getUser($userId);

        $item = $this->userSalaryService->get(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
        );
        if (!$item) {
            return response()->json([
                'code' => 'SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT',
                'message' => null,
                'payload' => null,
            ]);
        }
        $presenter = new UserSalaryPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateUserSalaryRequest $request): JsonResponse
    {
        $createCreateUserSalaryDTO = $request->createCreateUserSalaryDTO();
        $userId = Uuid::fromString($request->input('user_id'));

        $user = $this->userRepository->getUser($userId);
        $createCreateUserSalaryDTO->global_id = $user->global_company_user_id;
        $createCreateUserSalaryDTO->company_id = $user->company_id;

        $createdItem = $this->userSalaryService->create($createCreateUserSalaryDTO);

        $presenter = new UserSalaryPresenter($createdItem);

        return Json::item($presenter->getData());
    }

}
