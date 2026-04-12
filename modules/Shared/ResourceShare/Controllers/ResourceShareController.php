<?php

declare(strict_types=1);

namespace Modules\Shared\ResourceShare\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Shared\ResourceShare\Presenters\ResourceSharePresenter;
use Modules\Shared\ResourceShare\Repositories\ResourceShareRepository;
use Modules\Shared\ResourceShare\Services\ResourceShareService;

class ResourceShareController extends Controller
{
    public function __construct(
        private ResourceShareRepository $repository,
        private ResourceShareService $service,
    ) {}

    /**
     * GET /api/v1/resource-shares/pending
     * Returns all pending resource shares for the authenticated user's company
     */
    public function pending(): JsonResponse
    {
        $companyId = tenant('id');

        $shares = $this->repository->getPendingSharesForCompany($companyId);

        $data = $shares->map(fn ($share) => (new ResourceSharePresenter($share))->getData())->values()->all();

        return Json::item(["count"=>$shares->count(), "data"=>$data]);
    }

    /**
     * POST /api/v1/resource-shares/{id}/accept
     */
    public function accept(string $id): JsonResponse
    {
        $this->service->acceptShare($id);

        return Json::success('Share accepted successfully');
    }

    /**
     * POST /api/v1/resource-shares/{id}/reject
     */
    public function reject(string $id): JsonResponse
    {
        $this->service->rejectShare($id);

        return Json::success('Share rejected successfully');
    }
}
