<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Controllers\Customer;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoProduct\Presenters\Customer\EcoProductCustomerPresenter;
use Modules\Ecommerce\EcoProduct\Presenters\Customer\EcoProductCustomerDetailsPresenter;
use Modules\Ecommerce\EcoProduct\Requests\Customer\GetEcoProductListCustomerRequest;
use Modules\Ecommerce\EcoProduct\Requests\Customer\GetEcoProductCustomerRequest;
use Modules\Ecommerce\EcoProduct\Requests\Customer\SearchEcoProductCustomerRequest;
use Modules\Ecommerce\EcoProduct\Services\Customer\EcoProductCustomerService;
use Ramsey\Uuid\Uuid;

class EcoProductCustomerController extends Controller
{
    public function __construct(
        private EcoProductCustomerService $ecoProductService,
    ) {
    }

    public function index(GetEcoProductListCustomerRequest $request): JsonResponse
    {
        $list = $this->ecoProductService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 12),
            $request->get('category_id'),
            $request->get('brand_id'),
            $request->get('min_price'),
            $request->get('max_price'),
            $request->get('sort_by', 'created_at'),
            $request->get('sort_direction', 'desc')
        );

        return Json::items(EcoProductCustomerPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoProductCustomerRequest $request): JsonResponse
    {
        $product = $this->ecoProductService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoProductCustomerDetailsPresenter($product);

        return Json::item($presenter->getData());
    }

    public function search(SearchEcoProductCustomerRequest $request): JsonResponse
    {
        $results = $this->ecoProductService->search(
            $request->get('query'),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 12)
        );

        return Json::items(EcoProductCustomerPresenter::collection($results['data']), paginationSettings: $results['pagination']);
    }

    public function getByCategory(GetEcoProductListCustomerRequest $request): JsonResponse
    {
        $categoryId = $request->route('category_id');
        
        $products = $this->ecoProductService->getByCategory(
            Uuid::fromString($categoryId),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 12)
        );

        return Json::items(EcoProductCustomerPresenter::collection($products['data']), paginationSettings: $products['pagination']);
    }

    public function getFeatured(): JsonResponse
    {
        $products = $this->ecoProductService->getFeatured();

        return Json::items(EcoProductCustomerPresenter::collection($products));
    }

    public function getRelated(GetEcoProductCustomerRequest $request): JsonResponse
    {
        $productId = Uuid::fromString($request->route('id'));
        $relatedProducts = $this->ecoProductService->getRelated($productId, 6);

        return Json::items(EcoProductCustomerPresenter::collection($relatedProducts));
    }
}
