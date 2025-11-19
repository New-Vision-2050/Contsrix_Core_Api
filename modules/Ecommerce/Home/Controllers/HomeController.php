<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Home\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Banner\Models\Banner;
use Modules\Ecommerce\Banner\Presenters\BannerPresenter;
use Modules\Ecommerce\DealDay\Models\DealDay;
use Modules\Ecommerce\DealDay\Presenters\DealDayPresenter;
use Modules\Ecommerce\EcoCategory\Models\EcoCategory;
use Modules\Ecommerce\EcoCategory\Presenters\Dashboard\EcoCategoryPresenter;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\EcoProduct\Presenters\Customer\EcoProductCustomerPresenter;
use Modules\Ecommerce\EcoProduct\Presenters\Customer\EcoProductWithFeatureDealPresenter;
use Modules\Ecommerce\EcoProduct\Presenters\Customer\EcoProductWithDailyDealPresenter;
use Modules\Ecommerce\EcoProduct\Presenters\Customer\EcoProductWithFlashDealPresenter;
use Modules\Ecommerce\FeatureDeal\Models\FeatureDeal;
use Modules\Ecommerce\FeatureDeal\Presenters\FeatureDealPresenter;
use Modules\Ecommerce\SocialMedia\Models\SocialMedia;
use Modules\Ecommerce\SocialMedia\Presenters\SocialMediaPresenter;

class HomeController extends Controller
{
    public function banners(Request $request): JsonResponse
    {
        $type = $request->get('type', 'home');
        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);

        $list = Banner::where('type', $type)
            ->where('is_active', true)
            ->latest()
            ->forPage($page, $perPage)->orderBy('created_at', 'desc')->get();

            return Json::items(BannerPresenter::collection( $list),  paginationSettings: [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $list->count(),
                'last_page' => ceil($list->count() / $perPage),
            ]);
        }

    public function categories(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 12);
        $page = (int) $request->get('page', 1);

        $categories = EcoCategory::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->withCount('products')
            ->orderBy('priority')
            ->paginate($perPage, ['*'], 'page', $page);

        return Json::items(EcoCategoryPresenter::collection($categories), paginationSettings: [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $categories->count(),
            'last_page' => ceil($categories->count() / $perPage),
        ]);
    }
    public function latestProducts(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 12);
        $page = (int) $request->get('page', 1);

        $products = EcoProduct::where('is_visible', true)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return Json::items(
            EcoProductCustomerPresenter::collection($products),
            paginationSettings: [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $products->count(),
                'last_page' => ceil($products->count() / $perPage),
            ]
        );
    }

    public function featuredProducts(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 12);
        $page = (int) $request->get('page', 1);

        $products = EcoProduct::where('is_visible', true)
            ->where('is_featured', true)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return Json::items(
            EcoProductCustomerPresenter::collection($products),
            paginationSettings: [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $products->count(),
                'last_page' => ceil($products->count() / $perPage),
            ]
        );
    }

    public function discountedProducts(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 12);
        $page = (int) $request->get('page', 1);

        $products = EcoProduct::where('is_visible', true)
            ->where('discount_value', '>', 0)
            ->orderByDesc('discount_value')
            ->paginate($perPage, ['*'], 'page', $page);
 
        return Json::items(
            EcoProductCustomerPresenter::collection($products),
            paginationSettings: [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $products->count(),
                'last_page' => ceil($products->count() / $perPage),
            ]
        );
    }
    public function footer(Request $request): JsonResponse
    {
        $socialMedias = SocialMedia::get();
        $socialMediasData = $socialMedias->map(function ($socialMedia) {
            $presenter = new SocialMediaPresenter($socialMedia);
            return $presenter->getData();
        });
        return Json::item([
            'social_medias' => $socialMediasData,
         ]);
    }

    public function dailyDeal(Request $request): JsonResponse
    {
        $deal = DealDay::query()
            ->where('is_active', true)
            ->whereDate('date_offer', now()->format('Y-m-d'))
            ->with('product')
            ->first();

        if (!$deal || !$deal->product) {
            return response()->json([
                'code' => 'SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT',
                'message' => null,
                'payload' => null,
            ]);
        }

        return Json::item(
            (new EcoProductWithDailyDealPresenter($deal->product, $deal))->getData()
        );
    }

    public function flashDeals(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);

        $products = EcoProduct::query()
            ->where('is_visible', true)
            ->whereHas('flashDeals', function ($query) {
                $query->where('is_active', 1)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
            })
            ->with('flashDeals', function ($query) {
                $query->where('is_active', 1)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
            })
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $presentedProducts = $products->map(function ($product) {
            return (new EcoProductWithFlashDealPresenter($product))->getData();
        });

        return Json::items(
            $presentedProducts->values()->all(),
            paginationSettings: [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
                'result_count' => $products->count(),
            ]
        );
    }

    public function featureDeals(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);

        $featureDeals = FeatureDeal::query()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return Json::items(
            FeatureDealPresenter::collection($featureDeals),
            paginationSettings: [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $featureDeals->total(),
                'last_page' => $featureDeals->lastPage(),
                'result_count' => $featureDeals->count(),
            ]
        );
    }
}
