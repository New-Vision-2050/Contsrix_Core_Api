<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Advance\AdvanceRequest\Presenters\AdvanceRequestPresenter;
use Modules\Advance\AdvanceRequest\Requests\CreateAdvanceRequestRequest;
use Modules\Advance\AdvanceRequest\Requests\GetAdvanceRequestsRequest;
use Modules\Advance\AdvanceRequest\Requests\RejectAdvanceRequestRequest;
use Modules\Advance\AdvanceRequest\Services\AdvanceRequestService;
use Modules\User\Models\User;

class AdvanceRequestController extends Controller
{
    public function __construct(
        private readonly AdvanceRequestService $advanceRequestService,
    ) {}

    /**
     * Create an advance request (employee) and start its approval workflow.
     */
    public function store(CreateAdvanceRequestRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $advanceRequest = $this->advanceRequestService->create($user, $request->createDTO());

        return Json::item(
            (new AdvanceRequestPresenter($advanceRequest))->getData(),
            message: 'Advance request created successfully'
        );
    }

    /**
     * The authenticated employee's own advance requests.
     */
    public function myRequests(GetAdvanceRequestsRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $paginator = $this->advanceRequestService->myRequests((string) $user->id, $request->filters());

        return Json::items(
            AdvanceRequestPresenter::collection($paginator->items()),
            paginationSettings: $this->paginationSettings($paginator),
            message: 'Your advance requests retrieved successfully'
        );
    }

    /**
     * Admin list of all company advance requests (sidebar: طلبات السلف).
     */
    public function index(GetAdvanceRequestsRequest $request): JsonResponse
    {
        $paginator = $this->advanceRequestService->listForAdmin($request->filters());

        return Json::items(
            AdvanceRequestPresenter::collection($paginator->items()),
            paginationSettings: $this->paginationSettings($paginator),
            message: 'Advance requests retrieved successfully'
        );
    }

    public function show(string $id): JsonResponse
    {
        $advanceRequest = $this->advanceRequestService->getById($id);

        return Json::item(
            (new AdvanceRequestPresenter($advanceRequest))->getData(),
            message: 'Advance request retrieved successfully'
        );
    }

    /**
     * Cancel a pending advance request (owner only).
     */
    public function cancel(string $id): JsonResponse
    {
        $user = request()->user();
        abort_unless($user instanceof User, 401);

        $advanceRequest = $this->advanceRequestService->cancel($id, $user);

        return Json::item(
            (new AdvanceRequestPresenter($advanceRequest))->getData(),
            message: 'Advance request cancelled successfully'
        );
    }

    /**
     * Approve the pending workflow step assigned to the current user.
     */
    public function approve(string $id): JsonResponse
    {
        $advanceRequest = $this->advanceRequestService->approve($id);

        return Json::item(
            (new AdvanceRequestPresenter($advanceRequest))->getData(),
            message: 'Advance request approved successfully'
        );
    }

    /**
     * Reject the pending workflow step assigned to the current user.
     */
    public function reject(RejectAdvanceRequestRequest $request, string $id): JsonResponse
    {
        $advanceRequest = $this->advanceRequestService->reject($id, $request->reason());

        return Json::item(
            (new AdvanceRequestPresenter($advanceRequest))->getData(),
            message: 'Advance request rejected successfully'
        );
    }

    private function paginationSettings(LengthAwarePaginator $paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'next_page' => $paginator->currentPage() < $paginator->lastPage()
                ? $paginator->currentPage() + 1
                : $paginator->lastPage(),
            'last_page' => $paginator->lastPage(),
            'result_count' => $paginator->total(),
        ];
    }
}
