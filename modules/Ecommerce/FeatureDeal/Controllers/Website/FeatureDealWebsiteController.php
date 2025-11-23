<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Controllers\Website;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\FeatureDeal\Presenters\FeatureDealPresenter;
use Modules\Ecommerce\FeatureDeal\Requests\Website\GetFeatureDealListWebsiteRequest;
use Modules\Ecommerce\FeatureDeal\Requests\Website\GetFeatureDealWebsiteRequest;
use Modules\Ecommerce\FeatureDeal\Services\Website\FeatureDealWebsiteService;
use Ramsey\Uuid\Uuid;

class FeatureDealWebsiteController extends Controller
{
    public function __construct(
        private FeatureDealWebsiteService $featureDealService,
    ) {
    }

    public function index(GetFeatureDealListWebsiteRequest $request): JsonResponse
    {
        $list = $this->featureDealService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(FeatureDealPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetFeatureDealWebsiteRequest $request): JsonResponse
    {
        $item = $this->featureDealService->get(Uuid::fromString($request->route('id')));

        $presenter = new FeatureDealPresenter($item);

        return Json::item($presenter->getData(false)); // false = details view, not listing
    }
}

