<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\UserPrivilege\Handlers\DeleteUserPrivilegeHandler;
use Modules\UserInfo\UserPrivilege\Handlers\UpdateUserPrivilegeHandler;
use Modules\UserInfo\UserPrivilege\Presenters\UserPrivilegePresenter;
use Modules\UserInfo\UserPrivilege\Requests\CreateUserPrivilegeRequest;
use Modules\UserInfo\UserPrivilege\Requests\DeleteUserPrivilegeRequest;
use Modules\UserInfo\UserPrivilege\Requests\GetUserPrivilegeListRequest;
use Modules\UserInfo\UserPrivilege\Requests\GetUserPrivilegeRequest;
use Modules\UserInfo\UserPrivilege\Requests\UpdateUserPrivilegeRequest;
use Modules\UserInfo\UserPrivilege\Services\UserPrivilegeCRUDService;
use Modules\MedicalInsurance\Services\MedicalInsuranceSubscriptionCRUDService;
use Modules\Shared\Privilege\Services\PrivilegeCardConfigService;
use Ramsey\Uuid\Uuid;
use Stancl\Tenancy\Facades\Tenancy;

class UserPrivilegeController extends Controller
{
    public function __construct(
        private UserPrivilegeCRUDService $userPrivilegeService,
        private UpdateUserPrivilegeHandler $updateUserPrivilegeHandler,
        private DeleteUserPrivilegeHandler $deleteUserPrivilegeHandler,
        private UserRepository $userRepository,
        private MedicalInsuranceSubscriptionCRUDService $medicalInsuranceSubscriptionService,
    ) {
    }

    public function index(GetUserPrivilegeListRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));
        $user = $this->userRepository->getUser($userId);

        $list = $this->userPrivilegeService->list(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        // Pre-fetch subscriptions for health insurance privileges (indexed by medical_insurance_id).
        // The presenter uses this map to avoid N+1 queries.
        $subscriptionsByInsurance = $this->fetchSubscriptionsByInsurance($userId->toString());

        return Json::items(
            UserPrivilegePresenter::collection($list['data'], $subscriptionsByInsurance),
            paginationSettings: $list['pagination']
        );
    }

    /**
     * Fetch all medical insurance subscriptions for a user, indexed by medical_insurance_id.
     * Returns an empty array if the user has no subscriptions.
     */
    private function fetchSubscriptionsByInsurance(string $userId): array
    {
        $subs = $this->medicalInsuranceSubscriptionService->list(
            page: 1,
            perPage: 1000,
            filters: ['user_id' => $userId]
        );

        $indexed = [];
        foreach ($subs['data'] as $sub) {
            $indexed[$sub->medical_insurance_id][] = $sub;
        }

        return $indexed;
    }

    public function show(GetUserPrivilegeRequest $request): JsonResponse
    {
        $item = $this->userPrivilegeService->get(Uuid::fromString($request->route('id')));

        $presenter = new UserPrivilegePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateUserPrivilegeRequest $request): JsonResponse
    {
        $createCreateUserPrivilegeDTO = $request->createCreateUserPrivilegeDTO();
        $userId = Uuid::fromString($request->input('user_id'));

        return DB::transaction(function () use ($request, $createCreateUserPrivilegeDTO, $userId) {
            $user = $this->userRepository->getUser($userId);
            $createCreateUserPrivilegeDTO->company_id = $user->company_id;
            $createCreateUserPrivilegeDTO->global_id = $user->global_company_user_id;

            $createdItem = $this->userPrivilegeService->create($createCreateUserPrivilegeDTO);

            // Resolve privilege type from the created item's relationship (no separate DB query).
            $privilegeType = $createdItem->privilege?->type;

            // Process subscriptions if privilege is health_insurance type.
            $this->processSubscriptions(
                privilegeType: $privilegeType,
                subscriptions: $request->get('subscriptions', []),
                companyId: $user->company_id,
                createDTOs: fn () => $request->createSubscriptionDTOs($userId->toString()),
            );

            // Reload the privilege after tenant-conditional subscription processing
            // so the presenter has all eager-loaded relations.
            $createdItem->load([
                'privilege',
                'typePrivilege',
                'typeAllowance',
                'period',
                'medicalInsurance',
            ]);

            $subscriptionsByInsurance = $this->fetchSubscriptionsByInsurance($userId->toString());

            $presenter = new UserPrivilegePresenter($createdItem, $subscriptionsByInsurance);

            return Json::item($presenter->getData());
        });
    }

    public function update(UpdateUserPrivilegeRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $command = $request->createUpdateUserPrivilegeCommand();
            $this->updateUserPrivilegeHandler->handle($command);

            $item = $this->userPrivilegeService->get($command->getId());
            $item->load([
                'privilege',
                'typePrivilege',
                'typeAllowance',
                'period',
                'medicalInsurance',
            ]);

            $userId = $this->resolveUserIdFromPrivilege($item);

            // Process subscriptions if privilege is health_insurance type.
            $this->processSubscriptions(
                privilegeType: $item->privilege?->type,
                subscriptions: $request->get('subscriptions', []),
                companyId: $item->company_id,
                createDTOs: fn () => $request->createSubscriptionDTOs($userId),
            );

            $subscriptionsByInsurance = $userId
                ? $this->fetchSubscriptionsByInsurance($userId)
                : [];

            $presenter = new UserPrivilegePresenter($item, $subscriptionsByInsurance);

            return Json::item($presenter->getData());
        });
    }

    public function delete(DeleteUserPrivilegeRequest $request): JsonResponse
    {
        $this->deleteUserPrivilegeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Process subscription creation if the privilege type is health_insurance
     * and subscriptions are provided.
     *
     * Runs inside the caller's DB transaction so that user_privilege creation
     * and subscription creation are atomic.
     */
    private function processSubscriptions(?string $privilegeType, array $subscriptions, string $companyId, callable $createDTOs): void
    {
        if (empty($subscriptions) || $privilegeType !== PrivilegeCardConfigService::TYPE_HEALTH_INSURANCE) {
            return;
        }

        $dtos = $createDTOs();
        if (empty($dtos)) {
            return;
        }

        Tenancy::initialize($companyId);
        $this->medicalInsuranceSubscriptionService->createMany($dtos);
    }

    /**
     * Resolve the user UUID from a UserPrivilege record.
     */
    private function resolveUserIdFromPrivilege($userPrivilege): string
    {
        $userId = \Modules\User\Models\User::where('global_company_user_id', $userPrivilege->global_id)
            ->where('company_id', $userPrivilege->company_id)
            ->value('id');

        return $userId ? (string) $userId : '';
    }
}
