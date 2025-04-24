<?php

declare(strict_types=1);

namespace Modules\UserInfo\Social\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\Social\Handlers\UpdateSocialHandler;
use Modules\UserInfo\Social\Presenters\SocialPresenter;
use Modules\UserInfo\Social\Requests\GetSocialRequest;
use Modules\UserInfo\Social\Requests\UpdateSocialRequest;
use Modules\UserInfo\Social\Services\SocialCRUDService;
use Ramsey\Uuid\Uuid;

class SocialController extends Controller
{
    public function __construct(
        private SocialCRUDService $socialService,
        private UpdateSocialHandler $updateSocialHandler,
        private UserRepository $userRepository,
        private CompanyUserRepository $companyUserRepository,
    ) {
    }

    public function show(GetSocialRequest $request): JsonResponse
    {
        $user = $this->userRepository->getUser(Uuid::fromString($request->route('id')));
        $companyUser =$this->companyUserRepository->getCompanyUserGlobalId(Uuid::fromString($user->global_company_user_id));

        $item = $this->socialService->get(Uuid::fromString($companyUser->id));

        $presenter = new SocialPresenter($item);

        return Json::item($presenter->getData());
    }

    public function update(UpdateSocialRequest $request)//: JsonResponse
    {
        $command = $request->createUpdateSocialCommand();

        $user = $this->userRepository->getUser(Uuid::fromString($request->route('id')));

        $companyUser =$this->companyUserRepository->getCompanyUserGlobalId(Uuid::fromString($user->global_company_user_id));

        $command = $request->createUpdateSocialCommand();
        $command->companyUserId = Uuid::fromString($companyUser->id) ;

        $this->updateSocialHandler->handle($command);

        $item = $this->socialService->get($command->companyUserId);

        $presenter = new SocialPresenter($item);

        return Json::item( $presenter->getData());
    }

}
