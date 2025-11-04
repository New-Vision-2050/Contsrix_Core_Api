<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Home\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Banner\Models\Banner;
use Modules\Ecommerce\Banner\Presenters\BannerPresenter;
use Modules\Ecommerce\EcoCategory\Models\EcoCategory;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Illuminate\Http\Request;
use Modules\Ecommerce\SocialMedia\Models\SocialMedia;
use Modules\Ecommerce\SocialMedia\Presenters\SocialMediaPresenter;
use Modules\Ecommerce\EcoCategory\Presenters\Dashboard\EcoCategoryDashboardPresenter;
use Modules\Ecommerce\EcoProduct\Presenters\Customer\EcoProductCustomerPresenter;

class HomeController extends Controller
{
    public function index1(Request $request): JsonResponse
    {
        // Get home banners (active banners with type 'home')
        $banners = Banner::where('type', 'home')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform banners using presenter
        $bannersData = $banners->map(function ($banner) {
            $presenter = new BannerPresenter($banner);
            return $presenter->getData();
        });

        // Get active categories with products count
        $categories = EcoCategory::where('is_active', true)
            ->withCount('products')
            ->where('parent_id', null)
            ->get();

        // Transform categories using presenter
        $categoriesData = $categories->map(function ($category) {
            $presenter = new EcoCategoryDashboardPresenter($category);
            return $presenter->getData();
        });

        // Get latest products (active and visible)
        $latestProducts = EcoProduct::where('is_visible', true)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Transform products using presenter
        $latestProductsData = $latestProducts->map(function ($product) {
            $presenter = new EcoProductCustomerPresenter($product);
            return $presenter->getData();
        });

        // Get featured products (products with high ratings or marked as featured)
        $featuredProducts = EcoProduct::where('is_visible', true)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Transform featured products using presenter
        $featuredProductsData = $featuredProducts->map(function ($product) {
            $presenter = new EcoProductCustomerPresenter($product);
            return $presenter->getData();
        });

        // Get discounted products (products with discount > 0)
        $discountedProducts = EcoProduct::where('is_visible', true)
            ->where('discount_value', '>', 0)
            ->orderBy('discount_value', 'desc')
            ->limit(8)
            ->get();

        // Transform discounted products using presenter
        $discountedProductsData = $discountedProducts->map(function ($product) {
            $presenter = new EcoProductCustomerPresenter($product);
            return $presenter->getData();
        });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات الصفحة الرئيسية بنجاح',
            'data' => [
                'banners' => $bannersData,
                'categories' => $categoriesData,
                'latest_products' => $latestProductsData,
                'featured_products' => $featuredProductsData,
                'discounted_products' => $discountedProductsData,
            ],
            'meta' => [
                'total_banners' => $banners->count(),
                'total_categories' => $categories->count(),
                'total_latest_products' => $latestProducts->count(),
                'total_featured_products' => $featuredProducts->count(),
                'total_discounted_products' => $discountedProducts->count(),
            ],
        ]);
    }
    public function index2(Request $request): JsonResponse
    {
        // Get offers banners (active banners with type 'offers')
        $offersBanners = Banner::where('type', 'offers')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform banners using presenter
        $offersBannersData = $offersBanners->map(function ($banner) {
            $presenter = new BannerPresenter($banner);
            return $presenter->getData();
        });


        // Get flash sale products (products with high discount > 30%)
        $flashSaleProduct = EcoProduct::where('is_visible', true)
            ->where('discount_value', '>', 30)
            ->orderBy('discount_value', 'desc')
            ->first();

        // Transform flash sale product
        $flashSaleProductData = null;
        if ($flashSaleProduct) {
            $presenter = new EcoProductCustomerPresenter($flashSaleProduct);
            $flashSaleProductData = $presenter->getData();
        }

        // Get limited time offers (products with discount_value > 20%)
        $limitedTimeOffers = EcoProduct::where('is_visible', true)
            ->where('discount_value', '>', 20)
            ->where('discount_value', '<=', 30)
            ->orderBy('discount_value', 'desc')
            ->limit(8)
            ->get();

        // Transform limited time offers
        $limitedTimeOffersData = $limitedTimeOffers->map(function ($product) {
            $presenter = new EcoProductCustomerPresenter($product);
            return $presenter->getData();
        });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب العروض والخصومات بنجاح',
            'data' => [
                'offers_banners' => $offersBannersData,
                'flash_sale' => $flashSaleProductData,
                'limited_time_offers' => $limitedTimeOffersData,
            ],

            'meta' => [
                'total_offers_banners' => $offersBanners->count(),
                'total_flash_sale' => $flashSaleProduct ? 1 : 0,
                'total_limited_offers' => $limitedTimeOffers->count(),
            ],
        ]);
    }
    public function footer(Request $request): JsonResponse
    {
        $socialMedias = SocialMedia::get();
        $socialMediasData = $socialMedias->map(function ($socialMedia) {
            $presenter = new SocialMediaPresenter($socialMedia);
            return $presenter->getData();
        });
        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات الصفحة الرئيسية بنجاح',
            'data' => [
                'social_medias' => $socialMediasData,
            ],
            'meta' => [
                'total_social_medias' => $socialMedias->count(),
            ],
        ]);
    }
}
