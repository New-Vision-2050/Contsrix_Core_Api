<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\User\Repositories\UserRepository;

use Modules\UserInfo\EmploymentContract\Presenters\EmploymentContractPresenter;
use Modules\UserInfo\EmploymentContract\Requests\CreateEmploymentContractRequest;
use Modules\UserInfo\EmploymentContract\Requests\GetEmploymentContractListRequest;
use Modules\UserInfo\EmploymentContract\Requests\GetEmploymentContractRequest;
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
        $id = $request->route('id');
        $userId = $id ? Uuid::fromString($id) : Uuid::fromString((string) Auth::id());

        $user = $this->userRepository->getUser($userId);

        $item = $this->employmentContractService->get(
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

        $createdItem = $this->employmentContractService->create($createCreateEmploymentContractDTO,$request);

        $presenter = new EmploymentContractPresenter($createdItem);

        return Json::item($presenter->getData());
    }
}
