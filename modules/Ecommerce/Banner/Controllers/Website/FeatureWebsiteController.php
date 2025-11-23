<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Controllers\Website;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Banner\Presenters\FeaturePresenter;
use Modules\Ecommerce\Banner\Requests\Website\GetFeatureListWebsiteRequest;
use Modules\Ecommerce\Banner\Requests\Website\GetFeatureWebsiteRequest;
use Modules\Ecommerce\Banner\Services\Website\FeatureWebsiteService;
use Ramsey\Uuid\Uuid;

class FeatureWebsiteController extends Controller
{
    public function __construct(
        private FeatureWebsiteService $featureService,
    ) {
    }

    public function index(GetFeatureListWebsiteRequest $request): JsonResponse
    {
        $list = $this->featureService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
            $request->get('type')
        );

        return Json::items(FeaturePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetFeatureWebsiteRequest $request): JsonResponse
    {
        $item = $this->featureService->get(Uuid::fromString($request->route('id')));

        $presenter = new FeaturePresenter($item);

        return Json::item($presenter->getData());
    }
}

