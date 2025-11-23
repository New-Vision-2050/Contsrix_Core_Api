<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Controllers\Website;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoProduct\Presenters\Website\EcoProductWebsitePresenter;
use Modules\Ecommerce\EcoProduct\Presenters\Website\EcoProductWebsiteDetailsPresenter;
use Modules\Ecommerce\EcoProduct\Requests\Website\GetEcoProductListWebsiteRequest;
use Modules\Ecommerce\EcoProduct\Requests\Website\GetEcoProductWebsiteRequest;
use Modules\Ecommerce\EcoProduct\Services\Website\EcoProductWebsiteService;
use Ramsey\Uuid\Uuid;

class EcoProductWebsiteController extends Controller
{
    public function __construct(
        private EcoProductWebsiteService $ecoProductService,
    ) {
    }

    public function index(GetEcoProductListWebsiteRequest $request): JsonResponse
    {
        $list = $this->ecoProductService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 12),
            $request->get('sort_by', 'created_at'),
            $request->get('sort_direction', 'desc'),
            [],
            $request->get('order')
        );

        return Json::items(EcoProductWebsitePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoProductWebsiteRequest $request): JsonResponse
    {
        $product = $this->ecoProductService->get(
            Uuid::fromString($request->route('id')),
            ['category', 'subCategory', 'subSubCategory', 'brand', 'warehouse', 'countries']
        );

        $presenter = new EcoProductWebsiteDetailsPresenter($product);

        return Json::item($presenter->getData());
    }
}

