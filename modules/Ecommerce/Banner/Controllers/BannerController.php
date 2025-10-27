<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Banner\Handlers\DeleteBannerHandler;
use Modules\Ecommerce\Banner\Handlers\UpdateBannerHandler;
use Modules\Ecommerce\Banner\Presenters\BannerPresenter;
use Modules\Ecommerce\Banner\Requests\CreateBannerRequest;
use Modules\Ecommerce\Banner\Requests\DeleteBannerRequest;
use Modules\Ecommerce\Banner\Requests\GetBannerListRequest;
use Modules\Ecommerce\Banner\Requests\GetBannerRequest;
use Modules\Ecommerce\Banner\Requests\UpdateBannerRequest;
use Modules\Ecommerce\Banner\Services\BannerCRUDService;
use Modules\Ecommerce\Banner\Exports\BannerExport;
use Modules\Ecommerce\Banner\Requests\ExportBannerRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class BannerController extends Controller
{
    public function __construct(
        private BannerCRUDService $bannerService,
        private UpdateBannerHandler $updateBannerHandler,
        private DeleteBannerHandler $deleteBannerHandler,
    ) {
    }

    public function index(GetBannerListRequest $request): JsonResponse
    {
        $list = $this->bannerService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(BannerPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetBannerRequest $request): JsonResponse
    {
        $item = $this->bannerService->get(Uuid::fromString($request->route('id')));

        $presenter = new BannerPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateBannerRequest $request): JsonResponse
    {
        $file = $request->file('banner_image');
        $createdItem = $this->bannerService->create($request->createCreateBannerDTO(), $file);

        $presenter = new BannerPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateBannerRequest $request): JsonResponse
    {
        $command = $request->createUpdateBannerCommand();
        $file = $request->file('banner_image');
        
        $updatedItem = $this->updateBannerHandler->handle($command, $file);

        $presenter = new BannerPresenter($updatedItem);

        return Json::item($presenter->getData());
    }

    public function toggleStatus(GetBannerRequest $request): JsonResponse
    {
        $bannerId = Uuid::fromString($request->route('id'));
        $updatedBanner = $this->bannerService->toggleStatus($bannerId);
        
        $presenter = new BannerPresenter($updatedBanner);
        
        $message = $updatedBanner->is_active ? 'تم تفعيل البانر بنجاح' : 'تم إلغاء تفعيل البانر بنجاح';
        
        return Json::item($presenter->getData(), message: $message);
    }

    public function delete(DeleteBannerRequest $request): JsonResponse
    {
        $this->deleteBannerHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export banner to a file
     *
     * @param ExportBannerRequest $request
     */
    public function export(ExportBannerRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'banner.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new BannerExport($this->bannerService, $filters), $fileName);
    }
}
