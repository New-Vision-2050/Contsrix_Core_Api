<?php

declare(strict_types=1);

namespace Modules\UserInfo\Qualification\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\Qualification\Handlers\DeleteQualificationHandler;
use Modules\UserInfo\Qualification\Handlers\UpdateQualificationHandler;
use Modules\UserInfo\Qualification\Presenters\QualificationPresenter;
use Modules\UserInfo\Qualification\Requests\CreateQualificationRequest;
use Modules\UserInfo\Qualification\Requests\DeleteQualificationRequest;
use Modules\UserInfo\Qualification\Requests\GetQualificationListRequest;
use Modules\UserInfo\Qualification\Requests\GetQualificationRequest;
use Modules\UserInfo\Qualification\Requests\UpdateQualificationRequest;
use Modules\UserInfo\Qualification\Services\QualificationCRUDService;
use Ramsey\Uuid\Uuid;

class QualificationController extends Controller
{
    public function __construct(
        private QualificationCRUDService $qualificationService,
        private UpdateQualificationHandler $updateQualificationHandler,
        private DeleteQualificationHandler $deleteQualificationHandler,
        private UserRepository $userRepository

    ) {
    }

    public function index(GetQualificationListRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));
        $user = $this->userRepository->getUser($userId);

        $list = $this->qualificationService->list(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(QualificationPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetQualificationRequest $request): JsonResponse
    {
        $item = $this->qualificationService->get(Uuid::fromString($request->route('id')));

        $presenter = new QualificationPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateQualificationRequest $request)//: JsonResponse
    {
        $createCreateQualificationDTO = $request->createCreateQualificationDTO();
        $userId = Uuid::fromString($request->input('user_id'));

        $user = $this->userRepository->getUser($userId);
        $createCreateQualificationDTO->global_id = $user->global_company_user_id;
        $createCreateQualificationDTO->company_id = $user->company_id;

        $createdItem = $this->qualificationService->create($createCreateQualificationDTO);

        $this->qualificationService->uploadFile($createdItem,$request);

        $presenter = new QualificationPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateQualificationRequest $request)//: JsonResponse
    {
        $command = $request->createUpdateQualificationCommand();
       $this->updateQualificationHandler->handle($command);
        $item = $this->qualificationService->get($command->getId());

        $this->qualificationService->uploadFile($item,$request);

        $presenter = new QualificationPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteQualificationRequest $request): JsonResponse
    {
        $this->deleteQualificationHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
