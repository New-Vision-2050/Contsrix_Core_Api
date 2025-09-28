<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Controllers\Customer;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoAppSetting\Presenters\Customer\EcoAppSettingCustomerPresenter;
use Modules\Ecommerce\EcoAppSetting\Requests\Customer\GetEcoAppSettingCustomerRequest;
use Modules\Ecommerce\EcoAppSetting\Services\Customer\EcoAppSettingCustomerService;
use Ramsey\Uuid\Uuid;

class EcoAppSettingCustomerController extends Controller
{
    public function __construct(
        private EcoAppSettingCustomerService $ecoAppSettingService,
    ) {
    }

    /**
     * Get public app settings for customer app
     */
    public function getAppSettings(GetEcoAppSettingCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $settings = $this->ecoAppSettingService->getPublicAppSettings(Uuid::fromString($companyId));

        $presenter = new EcoAppSettingCustomerPresenter($settings);

        return Json::item($presenter->getData());
    }

    /**
     * Get theme settings
     */
    public function getThemeSettings(GetEcoAppSettingCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $themeSettings = $this->ecoAppSettingService->getThemeSettings(Uuid::fromString($companyId));

        return Json::item($themeSettings);
    }

    /**
     * Get display settings for products
     */
    public function getProductDisplaySettings(GetEcoAppSettingCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $displaySettings = $this->ecoAppSettingService->getProductDisplaySettings(Uuid::fromString($companyId));

        return Json::item($displaySettings);
    }

    /**
     * Get cart settings
     */
    public function getCartSettings(GetEcoAppSettingCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $cartSettings = $this->ecoAppSettingService->getCartSettings(Uuid::fromString($companyId));

        return Json::item($cartSettings);
    }

    /**
     * Get filter settings
     */
    public function getFilterSettings(GetEcoAppSettingCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $filterSettings = $this->ecoAppSettingService->getFilterSettings(Uuid::fromString($companyId));

        return Json::item($filterSettings);
    }

    /**
     * Get favorites settings
     */
    public function getFavoritesSettings(GetEcoAppSettingCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $favoritesSettings = $this->ecoAppSettingService->getFavoritesSettings(Uuid::fromString($companyId));

        return Json::item($favoritesSettings);
    }

    /**
     * Get product card settings
     */
    public function getProductCardSettings(GetEcoAppSettingCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $cardSettings = $this->ecoAppSettingService->getProductCardSettings(Uuid::fromString($companyId));

        return Json::item($cardSettings);
    }

    /**
     * Get app configuration for mobile app initialization
     */
    public function getAppConfig(GetEcoAppSettingCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $appConfig = $this->ecoAppSettingService->getAppConfig(Uuid::fromString($companyId));

        return Json::item($appConfig);
    }
}
