<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Controllers\Website;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoCategory\Presenters\Api\EcoCategoryCustomerPresenter;
use Modules\Ecommerce\EcoCategory\Requests\Website\GetEcoCategoryWebsiteRequest;
use Modules\Ecommerce\EcoCategory\Requests\Website\GetEcoCategoryListWebsiteRequest;
use Modules\Ecommerce\EcoCategory\Services\Website\EcoCategoryCRUDWebsiteService;
use Ramsey\Uuid\Uuid;

class EcoCategoryWebsiteController extends Controller
{
    public function __construct(
        private EcoCategoryCRUDWebsiteService $ecoCategoryService,
    ) {
    }

    public function index(GetEcoCategoryListWebsiteRequest $request): JsonResponse
    {
        $list = $this->ecoCategoryService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
            ['children']
        );

        return Json::items(EcoCategoryCustomerPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoCategoryWebsiteRequest $request): JsonResponse
    {
        $item = $this->ecoCategoryService->get(
            Uuid::fromString($request->route('id')),
            ['children.children']
        );

        $presenter = new EcoCategoryCustomerPresenter($item);

        return Json::item($presenter->getData());
    }
}

