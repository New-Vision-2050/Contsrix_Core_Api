<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Modules\Shared\InternalProcessType\Enums\InternalProcessEntityType;
use Modules\Shared\InternalProcessType\Presenters\InternalProcessTypePresenter;
use Modules\Shared\InternalProcessType\Services\InternalProcessTypeCRUDService;

class InternalProcessTypeController extends Controller
{
    public function __construct(
        private readonly InternalProcessTypeCRUDService $service,
    ) {}

    public function index(): JsonResponse
    {
        request()->validate([
            'entity_type' => ['required', 'string', Rule::in(InternalProcessEntityType::values())],
        ]);

        $types = $this->service->listActive(request()->input('entity_type'));

        return Json::items(
            mainItems: InternalProcessTypePresenter::collection($types),
            message: 'Internal process types retrieved successfully',
        );
    }
}
