<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Shared\InternalProcessType\Presenters\InternalProcessTypePresenter;
use Modules\Shared\InternalProcessType\Requests\CreateInternalProcessTypeRequest;
use Modules\Shared\InternalProcessType\Requests\UpdateInternalProcessTypeRequest;
use Modules\Shared\InternalProcessType\Services\InternalProcessTypeCRUDService;

class AdminInternalProcessTypeController extends Controller
{
    public function __construct(
        private readonly InternalProcessTypeCRUDService $service,
    ) {}

    public function index(): JsonResponse
    {
        $paginator = $this->service->list(
            entityType: request()->input('entity_type'),
            page: (int) request()->input('page', 1),
            perPage: (int) request()->input('per_page', 15),
        );

        return Json::items(
            mainItems: InternalProcessTypePresenter::collection($paginator->items()),
            paginationSettings: [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            message: 'Internal process types retrieved successfully',
        );
    }

    public function store(CreateInternalProcessTypeRequest $request): JsonResponse
    {
        $type = $this->service->create($request->createDTO());

        return Json::item(
            InternalProcessTypePresenter::single($type),
            message: 'Internal process type created successfully',
        );
    }

    public function update(UpdateInternalProcessTypeRequest $request, string $id): JsonResponse
    {
        $type = $this->service->update($request->createDTO($id));

        return Json::item(
            InternalProcessTypePresenter::single($type),
            message: 'Internal process type updated successfully',
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return Json::deleted('Internal process type deleted successfully');
    }
}
