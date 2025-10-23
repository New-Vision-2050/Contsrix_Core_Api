<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Coupon\Handlers\DeleteCouponHandler;
use Modules\Ecommerce\Coupon\Handlers\UpdateCouponHandler;
use Modules\Ecommerce\Coupon\Presenters\CouponPresenter;
use Modules\Ecommerce\Coupon\Requests\CreateCouponRequest;
use Modules\Ecommerce\Coupon\Requests\DeleteCouponRequest;
use Modules\Ecommerce\Coupon\Requests\GetCouponListRequest;
use Modules\Ecommerce\Coupon\Requests\GetCouponRequest;
use Modules\Ecommerce\Coupon\Requests\UpdateCouponRequest;
use Modules\Ecommerce\Coupon\Services\CouponCRUDService;
use Modules\Ecommerce\Coupon\Exports\CouponExport;
use Modules\Ecommerce\Coupon\Requests\ExportCouponRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class CouponController extends Controller
{
    public function __construct(
        private CouponCRUDService $couponService,
        private UpdateCouponHandler $updateCouponHandler,
        private DeleteCouponHandler $deleteCouponHandler,
    ) {
    }

    public function index(GetCouponListRequest $request): JsonResponse
    {
        $list = $this->couponService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(CouponPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetCouponRequest $request): JsonResponse
    {
        $item = $this->couponService->get(Uuid::fromString($request->route('id')));

        $presenter = new CouponPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateCouponRequest $request): JsonResponse
    {
        $createdItem = $this->couponService->create($request->createCouponDTO());

        $presenter = new CouponPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateCouponRequest $request): JsonResponse
    {
        $command = $request->createUpdateCouponCommand();
        $this->updateCouponHandler->handle($command);

        $item = $this->couponService->get($command->id);

        $presenter = new CouponPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteCouponRequest $request): JsonResponse
    {
        $this->deleteCouponHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function toggleStatus(GetCouponRequest $request): JsonResponse
    {
        $couponId = Uuid::fromString($request->route('id'));
        $updatedCoupon = $this->couponService->toggleStatus($couponId);
        
        $presenter = new CouponPresenter($updatedCoupon);
        
        $message = $updatedCoupon->is_active ? 'تم تفعيل القسيمة بنجاح' : 'تم إلغاء تفعيل القسيمة بنجاح';
        
        return Json::item($presenter->getData(), message: $message);
    }

    /**
     * Export coupon to a file
     *
     * @param ExportCouponRequest $request
     */
    public function export(ExportCouponRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'coupon.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new CouponExport($this->couponService, $filters), $fileName);
    }
}
