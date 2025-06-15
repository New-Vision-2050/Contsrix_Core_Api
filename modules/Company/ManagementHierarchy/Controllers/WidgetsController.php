<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Company\ManagementHierarchy\Presenters\WidgetsPresenter;
use Modules\Company\ManagementHierarchy\Services\ManagementHierarchyWidgetService;

class WidgetsController extends Controller
{
    public function __construct(
        private ManagementHierarchyWidgetService $widgetService
    ) {
    }

    /**
     * Get all widgets statistics in one call
     *
     * @return JsonResponse
     */
    public function getAllWidgets(): JsonResponse
    {
        $statistics = $this->widgetService->getAllWidgetsStatistics();
        $presenter = new WidgetsPresenter($statistics);
        
        return Json::item($presenter->getData());
    }

    /**
     * Get user count statistics
     *
     * @return JsonResponse
     */
    public function getUserCountStatistics(): JsonResponse
    {
        $statistics = $this->widgetService->getUserCountStatistics();
        
        return Json::item(['users' => $statistics]);
    }
    
    /**
     * Get branch count statistics
     *
     * @return JsonResponse
     */
    public function getBranchCountStatistics(): JsonResponse
    {
        $statistics = $this->widgetService->getBranchCountStatistics();
        
        return Json::item(['branches' => $statistics]);
    }
    
    /**
     * Get management count statistics
     *
     * @return JsonResponse
     */
    public function getManagementCountStatistics(): JsonResponse
    {
        $statistics = $this->widgetService->getManagementCountStatistics();
        
        return Json::item(['management' => $statistics]);
    }
    
    /**
     * Get department count statistics
     *
     * @return JsonResponse
     */
    public function getDepartmentCountStatistics(): JsonResponse
    {
        $statistics = $this->widgetService->getDepartmentCountStatistics();
        
        return Json::item(['departments' => $statistics]);
    }
}
