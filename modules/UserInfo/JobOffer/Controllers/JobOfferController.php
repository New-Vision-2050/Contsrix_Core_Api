<?php

declare(strict_types=1);

namespace Modules\UserInfo\JobOffer\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\JobOffer\Handlers\DeleteJobOfferHandler;
use Modules\UserInfo\JobOffer\Handlers\UpdateJobOfferHandler;
use Modules\UserInfo\JobOffer\Presenters\JobOfferPresenter;
use Modules\UserInfo\JobOffer\Requests\CreateJobOfferRequest;
use Modules\UserInfo\JobOffer\Requests\DeleteJobOfferRequest;
use Modules\UserInfo\JobOffer\Requests\GetJobOfferListRequest;
use Modules\UserInfo\JobOffer\Requests\GetJobOfferRequest;
use Modules\UserInfo\JobOffer\Requests\UpdateJobOfferRequest;
use Modules\UserInfo\JobOffer\Services\JobOfferCRUDService;
use Ramsey\Uuid\Uuid;

class JobOfferController extends Controller
{
    public function __construct(
        private JobOfferCRUDService $jobOfferService,
        private UserRepository $userRepository
    ) {
    }

    public function index(GetJobOfferRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));

        $user = $this->userRepository->getUser($userId);

        $item = $this->jobOfferService->get(
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

        $presenter = new JobOfferPresenter($item);

        return Json::item($item);
    }

    public function store(CreateJobOfferRequest $request): JsonResponse
    {
        $createCreateJobOfferDTO = $request->createCreateJobOfferDTO();
        $userId = Uuid::fromString($request->input('user_id'));

        $user = $this->userRepository->getUser($userId);
        $createCreateJobOfferDTO->global_id = $user->global_company_user_id;
        $createCreateJobOfferDTO->company_id = $user->company_id;

        $createdItem = $this->jobOfferService->create($createCreateJobOfferDTO);



        $presenter = new JobOfferPresenter($createdItem);

        return Json::item($presenter->getData());
    }

}
