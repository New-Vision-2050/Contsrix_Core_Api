<?php

declare(strict_types=1);

namespace Modules\UserInfo\Biography\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\Biography\Handlers\DeleteBiographyHandler;
use Modules\UserInfo\Biography\Handlers\UpdateBiographyHandler;
use Modules\UserInfo\Biography\Presenters\BiographyPresenter;
use Modules\UserInfo\Biography\Requests\CreateBiographyRequest;
use Modules\UserInfo\Biography\Requests\DeleteBiographyRequest;
use Modules\UserInfo\Biography\Requests\GetBiographyListRequest;
use Modules\UserInfo\Biography\Requests\GetBiographyRequest;
use Modules\UserInfo\Biography\Services\BiographyCRUDService;
use Ramsey\Uuid\Uuid;

class BiographyController extends Controller
{
    public function __construct(
        private BiographyCRUDService $biographyService,
        private DeleteBiographyHandler $deleteBiographyHandler,
        private UserRepository $userRepository,
        private CompanyUserCRUDService  $companyUserService,
    ) {
    }

    public function index(GetBiographyListRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));

        $user = $this->userRepository->getUser($userId);

        $item = $this->companyUserService->get(
            Uuid::fromString($user->global_company_user_id) ,
        );


        $presenter = new BiographyPresenter($item);
        return Json::item($presenter->getData());
    }

    public function show(GetBiographyRequest $request): JsonResponse
    {
        $item = $this->biographyService->get(Uuid::fromString($request->route('id')));

        $presenter = new BiographyPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateBiographyRequest $request)//: JsonResponse
    {
        $createCreateBiographyDTO = $request->createCreateBiographyDTO();
        $userId = Uuid::fromString($request->input('user_id'));

        $user = $this->userRepository->getUser($userId);
        $createCreateBiographyDTO->company_id = $user->company_id;
        $createCreateBiographyDTO->global_id = $user->global_company_user_id;

        $createdItem = $this->biographyService->create($createCreateBiographyDTO );

        $presenter = new BiographyPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteBiographyRequest $request): JsonResponse
    {
        $this->deleteBiographyHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
