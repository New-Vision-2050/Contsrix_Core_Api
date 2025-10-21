<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Controllers\Customer;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoShop\Presenters\Customer\EcoShopCustomerPresenter;
use Modules\Ecommerce\EcoShop\Requests\Customer\GetEcoShopCustomerRequest;
use Modules\Ecommerce\EcoShop\Services\Customer\EcoShopCustomerService;
use Ramsey\Uuid\Uuid;

class EcoShopCustomerController extends Controller
{
    public function __construct(
        private EcoShopCustomerService $ecoShopService,
    ) {
    }

    /**
     * Get public shop information
     */
    public function show(GetEcoShopCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $item = $this->ecoShopService->getPublicShopInfo(Uuid::fromString($companyId));

        $presenter = new EcoShopCustomerPresenter($item);

        return Json::item($presenter->getData());
    }

    /**
     * Get shop contact information
     */
    public function getContactInfo(GetEcoShopCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $contactInfo = $this->ecoShopService->getContactInfo(Uuid::fromString($companyId));

        return Json::item($contactInfo);
    }

    /**
     * Get shop social media links
     */
    public function getSocialMediaLinks(GetEcoShopCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $socialLinks = $this->ecoShopService->getSocialMediaLinks(Uuid::fromString($companyId));

        return Json::item($socialLinks);
    }

    /**
     * Get shop branding (logo, banner)
     */
    public function getBranding(GetEcoShopCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $branding = $this->ecoShopService->getBranding(Uuid::fromString($companyId));

        return Json::item($branding);
    }

    /**
     * Get shop basic info for header/footer
     */
    public function getBasicInfo(GetEcoShopCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $basicInfo = $this->ecoShopService->getBasicInfo(Uuid::fromString($companyId));

        return Json::item($basicInfo);
    }
}
