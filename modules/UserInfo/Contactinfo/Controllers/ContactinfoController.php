<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\Contactinfo\Handlers\UpdateAddressHandler;
use Modules\UserInfo\Contactinfo\Presenters\ContactinfoPresenter;
use Modules\UserInfo\Contactinfo\Requests\GetContactinfoRequest;
use Modules\UserInfo\Contactinfo\Requests\UpdateAddressRequest;
use Modules\UserInfo\Contactinfo\Requests\UpdateContactinfoRequest;
use Modules\UserInfo\Contactinfo\Services\ContactinfoCRUDService;
use Ramsey\Uuid\Uuid;

class ContactinfoController extends Controller
{
    public function __construct(
        private ContactinfoCRUDService $contactinfoService,
        private UpdateAddressHandler $updateAddressHandler,
        private UserRepository $userRepository,
        private CompanyUserRepository $companyUserRepository,
    ) {
    }

    public function show(GetContactinfoRequest $request)//: JsonResponse
    {
        $user = $this->userRepository->getUser(Uuid::fromString($request->route('id')));


        $item = $this->contactinfoService->get(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
        );


        if(!$item){
            return Json::item(null);
        }

        $presenter = new ContactinfoPresenter($item);

        return Json::item($presenter->getData());
    }
    public function update(UpdateContactinfoRequest $request): JsonResponse
    {
        $user = $this->userRepository->getUser(Uuid::fromString($request->route('id')));

        $updateContactinfoCommand = $request->createUpdateContactinfoCommand();

        $updateContactinfoCommand->company_id = $user->company_id;
        $updateContactinfoCommand->global_id = $user->global_company_user_id;


        $createdItem = $this->contactinfoService->create($updateContactinfoCommand);

        $presenter = new ContactinfoPresenter($createdItem);

        return Json::item( $presenter->getData());
    }

    public function updateAddress(UpdateAddressRequest $request)
    {
        $user = $this->userRepository->getUser(Uuid::fromString($request->route('id')));

        $companyUser =$this->companyUserRepository->getCompanyUserGlobalId(Uuid::fromString($user->global_company_user_id));

        $command = $request->createUpdateAddressCommand();
        $command->companyUserId = Uuid::fromString($companyUser->global_id) ;

        $this->updateAddressHandler->handle($command);
        $item = $this->contactinfoService->get( Uuid::fromString($user->company_id),$command->companyUserId);

        $presenter = new ContactinfoPresenter($item);

        return Json::item( $presenter->getData());
    }

}
