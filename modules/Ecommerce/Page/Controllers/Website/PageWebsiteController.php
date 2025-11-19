<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Controllers\Website;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Page\Presenters\PageWebsitePresenter;
use Modules\Ecommerce\Page\Requests\Website\GetPageByTypeWebsiteRequest;
use Modules\Ecommerce\Page\Services\Website\PageCRUDWebsiteService;

class PageWebsiteController extends Controller
{
    public function __construct(
        private PageCRUDWebsiteService $pageService,
    ) {
    }

    public function getByType(GetPageByTypeWebsiteRequest $request): JsonResponse
    {
        $type = $request->route('type');
        $page = $this->pageService->getByType($type);
        
        if (!$page) {
            return Json::error('Page not found', 404);
        }

        $presenter = new PageWebsitePresenter($page);
        return Json::item($presenter->getData(false)); 
    }
}

