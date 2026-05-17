<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\MedicalInsurance\Handlers\DeleteMedicalInsuranceSubscriptionHandler;
use Modules\MedicalInsurance\Handlers\UpdateMedicalInsuranceSubscriptionHandler;
use Modules\MedicalInsurance\Presenters\MedicalInsuranceSubscriptionPresenter;
use Modules\MedicalInsurance\Requests\CreateMedicalInsuranceSubscriptionRequest;
use Modules\MedicalInsurance\Requests\DeleteMedicalInsuranceSubscriptionRequest;
use Modules\MedicalInsurance\Requests\GetMedicalInsuranceSubscriptionListRequest;
use Modules\MedicalInsurance\Requests\GetMedicalInsuranceSubscriptionRequest;
use Modules\MedicalInsurance\Requests\UpdateMedicalInsuranceSubscriptionRequest;
use Modules\MedicalInsurance\Services\MedicalInsuranceSubscriptionCRUDService;
use Ramsey\Uuid\Uuid;

class MedicalInsuranceSubscriptionController extends Controller
{
    public function __construct(
        private MedicalInsuranceSubscriptionCRUDService $subscriptionService,
        private UpdateMedicalInsuranceSubscriptionHandler $updateHandler,
        private DeleteMedicalInsuranceSubscriptionHandler $deleteHandler,
    ) {
    }

    public function index(GetMedicalInsuranceSubscriptionListRequest $request): JsonResponse
    {
        $filters = array_filter([
            'user_id'              => $request->get('user_id'),
            'medical_insurance_id' => $request->get('medical_insurance_id'),
            'status'               => $request->has('status') ? (int) $request->get('status') : null,
        ], fn ($v) => $v !== null);

        $list = $this->subscriptionService->list(
            page: (int) $request->get('page', 1),
            perPage: (int) $request->get('per_page', 10),
            filters: $filters,
        );

        return Json::items(
            MedicalInsuranceSubscriptionPresenter::collection($list['data']),
            paginationSettings: $list['pagination']
        );
    }

    public function show(GetMedicalInsuranceSubscriptionRequest $request): JsonResponse
    {
        $item = $this->subscriptionService->get(Uuid::fromString($request->route('id')));

        return Json::item((new MedicalInsuranceSubscriptionPresenter($item))->getData());
    }

    public function store(CreateMedicalInsuranceSubscriptionRequest $request): JsonResponse
    {
        $item = $this->subscriptionService->create($request->createDTO());

        return Json::item((new MedicalInsuranceSubscriptionPresenter($item))->getData());
    }

    public function update(UpdateMedicalInsuranceSubscriptionRequest $request): JsonResponse
    {
        $command = $request->createCommand();
        $this->updateHandler->handle($command);

        $item = $this->subscriptionService->get($command->getId());

        return Json::item((new MedicalInsuranceSubscriptionPresenter($item))->getData());
    }

    public function delete(DeleteMedicalInsuranceSubscriptionRequest $request): JsonResponse
    {
        $this->deleteHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
