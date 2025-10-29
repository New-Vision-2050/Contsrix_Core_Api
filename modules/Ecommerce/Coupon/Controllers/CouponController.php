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
use Modules\Ecommerce\Coupon\Requests\Dashboard\ExportCouponRequest;
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
     * Export coupons to Excel or CSV
     */
    public function export(ExportCouponRequest $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $couponIds = $request->input('ids');
        $format = $request->input('format', 'xlsx');
        
        $filters = [
            'title' => $request->input('title'),
            'code' => $request->input('code'),
            'coupon_type' => $request->input('coupon_type'),
            'coupon_types' => $request->input('coupon_types'),
            'discount_type' => $request->input('discount_type'),
            'discount_types' => $request->input('discount_types'),
            'is_active' => $request->input('is_active'),
            'company_id' => $request->input('company_id'),
            'company_ids' => $request->input('company_ids'),
            'customer_id' => $request->input('customer_id'),
            'customer_ids' => $request->input('customer_ids'),
            'has_customer' => $request->input('has_customer'),
            'discount_amount_from' => $request->input('discount_amount_from'),
            'discount_amount_to' => $request->input('discount_amount_to'),
            'min_purchase_from' => $request->input('min_purchase_from'),
            'min_purchase_to' => $request->input('min_purchase_to'),
            'max_discount_from' => $request->input('max_discount_from'),
            'max_discount_to' => $request->input('max_discount_to'),
            'max_usage_per_user' => $request->input('max_usage_per_user'),
            'max_usage_per_user_from' => $request->input('max_usage_per_user_from'),
            'max_usage_per_user_to' => $request->input('max_usage_per_user_to'),
            'unlimited_usage' => $request->input('unlimited_usage'),
            'start_date_from' => $request->input('start_date_from'),
            'start_date_to' => $request->input('start_date_to'),
            'expire_date_from' => $request->input('expire_date_from'),
            'expire_date_to' => $request->input('expire_date_to'),
            'status' => $request->input('status'),
            'high_value' => $request->input('high_value'),
            'created_from' => $request->input('created_from'),
            'created_to' => $request->input('created_to'),
            'created_in_last_days' => $request->input('created_in_last_days'),
            'expiring_in_next_days' => $request->input('expiring_in_next_days'),
        ];

        if ($format === 'csv') {
            return $this->couponService->exportToCsv($couponIds, $filters);
        }

        return $this->couponService->exportToExcel($couponIds, $filters);
    }
}
