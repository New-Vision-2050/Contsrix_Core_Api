<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Controllers\Website;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Banner\Presenters\BannerPresenter;
use Modules\Ecommerce\Banner\Requests\Website\GetBannerListWebsiteRequest;
use Modules\Ecommerce\Banner\Requests\Website\GetBannerWebsiteRequest;
use Modules\Ecommerce\Banner\Services\Website\BannerWebsiteService;
use Ramsey\Uuid\Uuid;

class BannerWebsiteController extends Controller
{
    public function __construct(
        private BannerWebsiteService $bannerService,
    ) {
    }

    public function index(GetBannerListWebsiteRequest $request): JsonResponse
    {
        $list = $this->bannerService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
            $request->get('type')
        );

        return Json::items(BannerPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetBannerWebsiteRequest $request): JsonResponse
    {
        $item = $this->bannerService->get(Uuid::fromString($request->route('id')));

        $presenter = new BannerPresenter($item);

        return Json::item($presenter->getData());
    }
}

