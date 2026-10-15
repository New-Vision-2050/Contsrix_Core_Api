<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Advance\AdvanceRequest\Presenters\UserAdvancePolicyPresenter;
use Modules\Advance\AdvanceRequest\Requests\SyncUserAdvancePoliciesRequest;
use Modules\Advance\AdvanceRequest\Services\UserAdvancePolicyService;
use Modules\User\Models\User;

class UserAdvancePolicyController extends Controller
{
    public function __construct(
        private readonly UserAdvancePolicyService $userAdvancePolicyService,
    ) {}

    /**
     * Assign (sync) multiple advance policies to a user.
     * The sent set replaces the user's current assignments.
     */
    public function sync(SyncUserAdvancePoliciesRequest $request, string $userId): JsonResponse
    {
        $user = User::query()->findOrFail($userId);

        $this->userAdvancePolicyService->syncForUser(
            $userId,
            $request->advancePolicyIds(),
            (string) $request->user()?->id,
        );

        $rows = $this->userAdvancePolicyService->listWithAvailabilityForUser($user);

        return Json::items(
            UserAdvancePolicyPresenter::collection($rows),
            message: 'Advance policies assigned successfully'
        );
    }

    /**
     * A user's assigned advance policies with live availability (admin, user profile).
     */
    public function forUser(string $userId): JsonResponse
    {
        $user = User::query()->findOrFail($userId);

        $rows = $this->userAdvancePolicyService->listWithAvailabilityForUser($user);

        return Json::items(
            UserAdvancePolicyPresenter::collection($rows),
            message: 'User advance policies retrieved successfully'
        );
    }

    /**
     * The authenticated employee's own advance policies with availability (mobile).
     */
    public function my(): JsonResponse
    {
        $user = request()->user();
        abort_unless($user instanceof User, 401);

        $rows = $this->userAdvancePolicyService->listWithAvailabilityForUser($user);

        return Json::items(
            UserAdvancePolicyPresenter::collection($rows),
            message: 'Your advance policies retrieved successfully'
        );
    }
}
