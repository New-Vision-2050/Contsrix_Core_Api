<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Controllers\Website;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\FlashDeal\Presenters\FlashDealPresenter;
use Modules\Ecommerce\FlashDeal\Requests\Website\GetFlashDealListWebsiteRequest;
use Modules\Ecommerce\FlashDeal\Requests\Website\GetFlashDealWebsiteRequest;
use Modules\Ecommerce\FlashDeal\Services\Website\FlashDealWebsiteService;
use Ramsey\Uuid\Uuid;

class FlashDealWebsiteController extends Controller
{
    public function __construct(
        private FlashDealWebsiteService $flashDealService,
    ) {
    }

    public function index(GetFlashDealListWebsiteRequest $request): JsonResponse
    {
        $list = $this->flashDealService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(FlashDealPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetFlashDealWebsiteRequest $request): JsonResponse
    {
        $item = $this->flashDealService->get(Uuid::fromString($request->route('id')));

        $presenter = new FlashDealPresenter($item);

        return Json::item($presenter->getData(false)); // false = details view, not listing
    }
}

