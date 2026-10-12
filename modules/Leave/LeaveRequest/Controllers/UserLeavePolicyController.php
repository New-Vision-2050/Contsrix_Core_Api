<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Leave\LeaveRequest\Presenters\UserLeavePolicyPresenter;
use Modules\Leave\LeaveRequest\Requests\GetUserLeavePoliciesRequest;
use Modules\Leave\LeaveRequest\Requests\SyncUserLeavePoliciesRequest;
use Modules\Leave\LeaveRequest\Services\UserLeavePolicyService;
use Modules\User\Models\User;

class UserLeavePolicyController extends Controller
{
    public function __construct(
        private readonly UserLeavePolicyService $userLeavePolicyService,
    ) {}

    /**
     * Assign (sync) multiple leave policies to a user.
     * The sent set replaces the user's current assignments.
     */
    public function sync(SyncUserLeavePoliciesRequest $request, string $userId): JsonResponse
    {
        $user = User::query()->findOrFail($userId);

        $this->userLeavePolicyService->syncForUser(
            $userId,
            $request->leavePolicyIds(),
            (string) $request->user()?->id,
        );

        $rows = $this->userLeavePolicyService->listWithBalancesForUser($user);

        return Json::items(
            UserLeavePolicyPresenter::collection($rows),
            message: 'Leave policies assigned successfully'
        );
    }

    /**
     * A user's assigned leave policies with live yearly balances (admin).
     */
    public function forUser(GetUserLeavePoliciesRequest $request, string $userId): JsonResponse
    {
        $user = User::query()->findOrFail($userId);

        $rows = $this->userLeavePolicyService->listWithBalancesForUser($user, $request->year());

        return Json::items(
            UserLeavePolicyPresenter::collection($rows),
            message: 'User leave policies retrieved successfully'
        );
    }

    /**
     * The authenticated employee's own leave policies with balances (mobile).
     */
    public function my(GetUserLeavePoliciesRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $rows = $this->userLeavePolicyService->listWithBalancesForUser($user, $request->year());

        return Json::items(
            UserLeavePolicyPresenter::collection($rows),
            message: 'Your leave policies retrieved successfully'
        );
    }
}
