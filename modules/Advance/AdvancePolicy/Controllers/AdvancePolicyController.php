<?php

declare(strict_types=1);

namespace Modules\Advance\AdvancePolicy\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Advance\AdvancePolicy\Presenters\AdvancePolicyPresenter;
use Modules\Advance\AdvancePolicy\Requests\CreateAdvancePolicyRequest;
use Modules\Advance\AdvancePolicy\Requests\GetAdvancePolicyListRequest;
use Modules\Advance\AdvancePolicy\Requests\UpdateAdvancePolicyRequest;
use Modules\Advance\AdvancePolicy\Services\AdvancePolicyCRUDService;

class AdvancePolicyController extends Controller
{
    public function __construct(
        private readonly AdvancePolicyCRUDService $advancePolicyService,
    ) {}

    public function index(GetAdvancePolicyListRequest $request): JsonResponse
    {
        $paginator = $this->advancePolicyService->list($request->validated());

        return Json::items(
            AdvancePolicyPresenter::collection($paginator->items()),
            paginationSettings: $this->paginationSettings($paginator),
            message: 'Advance policies retrieved successfully'
        );
    }

    public function store(CreateAdvancePolicyRequest $request): JsonResponse
    {
        $policy = $this->advancePolicyService->create($request->createDTO());

        return Json::item(
            (new AdvancePolicyPresenter($policy))->getData(),
            message: 'Advance policy created successfully'
        );
    }

    public function show(string $id): JsonResponse
    {
        $policy = $this->advancePolicyService->get($id);

        return Json::item(
            (new AdvancePolicyPresenter($policy))->getData(),
            message: 'Advance policy retrieved successfully'
        );
    }

    public function update(UpdateAdvancePolicyRequest $request, string $id): JsonResponse
    {
        $policy = $this->advancePolicyService->update($id, $request->createDTO());

        return Json::item(
            (new AdvancePolicyPresenter($policy))->getData(),
            message: 'Advance policy updated successfully'
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $this->advancePolicyService->delete($id);

        return Json::deleted();
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
