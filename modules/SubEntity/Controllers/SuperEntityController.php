<?php

declare(strict_types=1);

namespace Modules\SubEntity\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\SubEntity\Services\SuperEntityService;
use Modules\SubEntity\Presenters\SuperEntityPresenter;
use Modules\SubEntity\Requests\GetSuperEntityAttributesRequest;

class SuperEntityController extends Controller
{
    public function __construct(
        private SuperEntityService $superEntityService,
    ) {
    }

    public function index(): JsonResponse
    {
        $list = $this->superEntityService->list(request()->get('search'));

        return Json::items(SuperEntityPresenter::collection($list));
    }

    public function getAvailableAttributes(GetSuperEntityAttributesRequest $request): JsonResponse
    {
        $attributes = $this->superEntityService->getAvailableAttributes($request->get('super_entity_id'));

        return Json::items($attributes);
    }
}
