<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\UserEducationalCourse\Handlers\DeleteUserEducationalCourseHandler;
use Modules\UserInfo\UserEducationalCourse\Handlers\UpdateUserEducationalCourseHandler;
use Modules\UserInfo\UserEducationalCourse\Presenters\UserEducationalCoursePresenter;
use Modules\UserInfo\UserEducationalCourse\Requests\CreateUserEducationalCourseRequest;
use Modules\UserInfo\UserEducationalCourse\Requests\DeleteUserEducationalCourseRequest;
use Modules\UserInfo\UserEducationalCourse\Requests\GetUserEducationalCourseListRequest;
use Modules\UserInfo\UserEducationalCourse\Requests\GetUserEducationalCourseRequest;
use Modules\UserInfo\UserEducationalCourse\Requests\UpdateUserEducationalCourseRequest;
use Modules\UserInfo\UserEducationalCourse\Services\UserEducationalCourseCRUDService;
use Ramsey\Uuid\Uuid;

class UserEducationalCourseController extends Controller
{
    public function __construct(
        private UserEducationalCourseCRUDService $userEducationalCourseService,
        private UpdateUserEducationalCourseHandler $updateUserEducationalCourseHandler,
        private DeleteUserEducationalCourseHandler $deleteUserEducationalCourseHandler,
        private UserRepository $userRepository

    ) {
    }

    public function index(GetUserEducationalCourseListRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));
        $user = $this->userRepository->getUser($userId);

        $list = $this->userEducationalCourseService->list(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(UserEducationalCoursePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetUserEducationalCourseRequest $request): JsonResponse
    {
        $item = $this->userEducationalCourseService->get(Uuid::fromString($request->route('id')));

        $presenter = new UserEducationalCoursePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateUserEducationalCourseRequest $request): JsonResponse
    {
        $createCreateUserEducationalCourseDTO = $request->createCreateUserEducationalCourseDTO();
        $userId = Uuid::fromString($request->input('user_id'));

         $user = $this->userRepository->getUser($userId);
        $createCreateUserEducationalCourseDTO->global_id = $user->global_company_user_id;
        $createCreateUserEducationalCourseDTO->company_id = $user->company_id;

        $createdItem = $this->userEducationalCourseService->create($createCreateUserEducationalCourseDTO);

        $presenter = new UserEducationalCoursePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateUserEducationalCourseRequest $request): JsonResponse
    {
        $command = $request->createUpdateUserEducationalCourseCommand();
        $this->updateUserEducationalCourseHandler->handle($command);

        $item = $this->userEducationalCourseService->get($command->getId());

        $presenter = new UserEducationalCoursePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteUserEducationalCourseRequest $request): JsonResponse
    {
        $this->deleteUserEducationalCourseHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
