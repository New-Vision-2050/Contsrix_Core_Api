<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
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
            message: 'Internal procedure settings retrieved successfully',
        );
    }

    public function formOptions(): JsonResponse
    {
        $type = request()->input('type');

        $cases = $type !== null
            ? InternalProcessForm::forType($type)
            : InternalProcessForm::cases();

        $forms = array_map(
            static fn (InternalProcessForm $form): array => $form->toDefinition(),
            $cases,
        );

        return Json::items(
            mainItems: $forms,
            message: 'Internal procedure setting forms retrieved successfully',
        );
    }

    public function formsConditions(): JsonResponse
    {
        $formKey = request()->input('type');
        $form    = $formKey !== null ? InternalProcessForm::tryFrom($formKey) : null;

        $conditions = $form !== null
            ? $form->conditions()
            : InternalProcessCondition::cases();

        $definitions = array_map(
            static fn (InternalProcessCondition $condition): array => $condition->toDefinition(),
            $conditions,
        );

        return Json::items(
            mainItems: $definitions,
            message: 'Forms conditions retrieved successfully',
        );
    }

    public function store(CreateInternalProcessTypeRequest $request): JsonResponse
    {
        $type = $this->service->create($request->createDTO());

        return Json::item(
            InternalProcessTypePresenter::single($type),
            message: 'Internal procedure setting created successfully',
        );
    }

    public function update(UpdateInternalProcessTypeRequest $request, string $id): JsonResponse
    {
        $type = $this->service->update($request->createDTO($id));

        return Json::item(
            InternalProcessTypePresenter::single($type),
            message: 'Internal procedure setting updated successfully',
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return Json::deleted('Internal procedure setting deleted successfully');
    }
}
