<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\ContractualRelationship\Presenters\ContractualRelationshipPresenter;
use Modules\UserInfo\ContractualRelationship\Presenters\ContractualRelationshipTypePresenter;
use Modules\UserInfo\ContractualRelationship\Requests\GetContractualRelationshipRequest;
use Modules\UserInfo\ContractualRelationship\Requests\GetContractualRelationshipTypesRequest;
use Modules\UserInfo\ContractualRelationship\Requests\UpdateContractualRelationshipRequest;
use Modules\UserInfo\ContractualRelationship\Services\ContractualRelationshipCRUDService;
use Modules\UserInfo\ContractualRelationship\Services\ContractualRelationshipTypeService;
use Ramsey\Uuid\Uuid;

class ContractualRelationshipController extends Controller
{
    public function __construct(
        private ContractualRelationshipCRUDService $contractualRelationshipService,
        private ContractualRelationshipTypeService $contractualRelationshipTypeService,
        private UserRepository $userRepository,
    ) {
    }

    public function show(GetContractualRelationshipRequest $request): JsonResponse
    {
        $user = $this->userRepository->getUser(Uuid::fromString($request->route('id')));

        $item = $this->contractualRelationshipService->get(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
        );

        if (!$item) {
            return Json::item(null);
        }

        $presenter = new ContractualRelationshipPresenter($item);

        return Json::item($presenter->getData());
    }

    public function update(UpdateContractualRelationshipRequest $request): JsonResponse
    {
        $user = $this->userRepository->getUser(Uuid::fromString($request->route('id')));

        $command = $request->createUpdateContractualRelationshipCommand();

        $command->company_id = $user->company_id;
        $command->global_id = $user->global_company_user_id;

        $createdItem = $this->contractualRelationshipService->create($command);

        $presenter = new ContractualRelationshipPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function getTypes(GetContractualRelationshipTypesRequest $request): JsonResponse
    {
        $types = $this->contractualRelationshipTypeService->getAllActiveTypes();

        $presentedTypes = ContractualRelationshipTypePresenter::collection($types);

        return Json::items($presentedTypes);
    }
}
