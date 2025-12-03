<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Controllers\Website;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Banner\Presenters\StoreBranchPresenter;
use Modules\Ecommerce\Banner\Requests\Website\GetStoreBranchListWebsiteRequest;
use Modules\Ecommerce\Banner\Requests\Website\GetStoreBranchWebsiteRequest;
use Modules\Ecommerce\Banner\Services\Website\StoreBranchWebsiteService;
use Ramsey\Uuid\Uuid;

class StoreBranchWebsiteController extends Controller
{
    public function __construct(
        private StoreBranchWebsiteService $storeBranchService,
    ) {
    }

    public function index(GetStoreBranchListWebsiteRequest $request): JsonResponse
    {
        $list = $this->storeBranchService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
            $request->get('type')
        );

        return Json::items(StoreBranchPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetStoreBranchWebsiteRequest $request): JsonResponse
    {
        $item = $this->storeBranchService->get(Uuid::fromString($request->route('id')));

        $presenter = new StoreBranchPresenter($item);

        return Json::item($presenter->getData());
    }
}

