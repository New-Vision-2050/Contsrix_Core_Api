<?php

declare(strict_types=1);

namespace Modules\Shared\ProfessionalBodie\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\ProfessionalBodie\Handlers\DeleteProfessionalBodieHandler;
use Modules\Shared\ProfessionalBodie\Handlers\UpdateProfessionalBodieHandler;
use Modules\Shared\ProfessionalBodie\Presenters\ProfessionalBodiePresenter;
use Modules\Shared\ProfessionalBodie\Requests\CreateProfessionalBodieRequest;
use Modules\Shared\ProfessionalBodie\Requests\DeleteProfessionalBodieRequest;
use Modules\Shared\ProfessionalBodie\Requests\GetProfessionalBodieListRequest;
use Modules\Shared\ProfessionalBodie\Requests\GetProfessionalBodieRequest;
use Modules\Shared\ProfessionalBodie\Requests\UpdateProfessionalBodieRequest;
use Modules\Shared\ProfessionalBodie\Services\ProfessionalBodieCRUDService;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\Uuid;

class ProfessionalBodieController extends Controller
{
    public function __construct(
        private ProfessionalBodieCRUDService $professionalBodieService,
        private UpdateProfessionalBodieHandler $updateProfessionalBodieHandler,
        private DeleteProfessionalBodieHandler $deleteProfessionalBodieHandler,
        private UserRepository $userRepository
    ) {
    }

    public function index(GetProfessionalBodieListRequest $request)//: JsonResponse
    {
        $userId = $request->route('id')? Uuid::fromString($request->route('id')) : auth()->user()->id;

        $user = $this->userRepository->getUser($userId);

        $getCode = $this->professionalBodieService->getCodes(
        Uuid::fromString($user->company_id),
        Uuid::fromString($user->global_company_user_id),
        );
        if(!$getCode){
            return Json::items($getCode);
        }


        $list = $this->professionalBodieService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(ProfessionalBodiePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetProfessionalBodieRequest $request): JsonResponse
    {
        $item = $this->professionalBodieService->get(Uuid::fromString($request->route('id')));

        $presenter = new ProfessionalBodiePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateProfessionalBodieRequest $request): JsonResponse
    {
        $createdItem = $this->professionalBodieService->create($request->createCreateProfessionalBodieDTO());

        $presenter = new ProfessionalBodiePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateProfessionalBodieRequest $request): JsonResponse
    {
        $command = $request->createUpdateProfessionalBodieCommand();
        $this->updateProfessionalBodieHandler->handle($command);

        $item = $this->professionalBodieService->get($command->getId());

        $presenter = new ProfessionalBodiePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteProfessionalBodieRequest $request): JsonResponse
    {
        $this->deleteProfessionalBodieHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
