<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\Coupon\DTO\CreateCouponDTO;
use Modules\Ecommerce\Coupon\Models\Coupon;
use Modules\Ecommerce\Coupon\Repositories\CouponRepository;
use Modules\Ecommerce\Coupon\Exports\CouponExport;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class CouponCRUDService
{
    use HasExportService;

    public function __construct(
        private CouponRepository $repository,
    ) {
    }

    public function create(CreateCouponDTO $createCouponDTO): Coupon
    {
         return $this->repository->createCoupon($createCouponDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Coupon
    {
        return $this->repository->getCoupon(
            id: $id,
        );
    }

    public function toggleStatus(UuidInterface $id): Coupon
    {
        $coupon = $this->repository->getCoupon($id);
        
        // Toggle the is_active status
        $newStatus = !$coupon->is_active;
        
        return $this->repository->updateCoupon($id, ['is_active' => $newStatus]);
    }

    /**
     * Export coupons to Excel
     */
    public function exportToExcel(array $couponIds = null, array $filters = []): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $query = Coupon::with(['company', 'customer']);

        // Apply filters
        if ($couponIds) {
            $query->whereIn('id', $couponIds);
        }

        if (isset($filters['title'])) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }

        if (isset($filters['code'])) {
            $query->where('code', 'like', '%' . $filters['code'] . '%');
        }

        if (isset($filters['coupon_type'])) {
            $query->where('coupon_type', $filters['coupon_type']);
        }

        if (isset($filters['coupon_types'])) {
            $query->whereIn('coupon_type', $filters['coupon_types']);
        }

        if (isset($filters['discount_type'])) {
            $query->where('discount_type', $filters['discount_type']);
        }

        if (isset($filters['discount_types'])) {
            $query->whereIn('discount_type', $filters['discount_types']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['company_ids'])) {
            $query->whereIn('company_id', $filters['company_ids']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['customer_ids'])) {
            $query->whereIn('customer_id', $filters['customer_ids']);
        }

        if (isset($filters['has_customer'])) {
            if ($filters['has_customer']) {
                $query->whereNotNull('customer_id');
            } else {
                $query->whereNull('customer_id');
            }
        }

        if (isset($filters['discount_amount_from'])) {
            $query->where('discount_amount', '>=', $filters['discount_amount_from']);
        }

        if (isset($filters['discount_amount_to'])) {
            $query->where('discount_amount', '<=', $filters['discount_amount_to']);
        }

        if (isset($filters['min_purchase_from'])) {
            $query->where('min_purchase', '>=', $filters['min_purchase_from']);
        }

        if (isset($filters['min_purchase_to'])) {
            $query->where('min_purchase', '<=', $filters['min_purchase_to']);
        }

        if (isset($filters['max_discount_from'])) {
            $query->where('max_discount', '>=', $filters['max_discount_from']);
        }

        if (isset($filters['max_discount_to'])) {
            $query->where('max_discount', '<=', $filters['max_discount_to']);
        }

        if (isset($filters['max_usage_per_user'])) {
            $query->where('max_usage_per_user', $filters['max_usage_per_user']);
        }

        if (isset($filters['max_usage_per_user_from'])) {
            $query->where('max_usage_per_user', '>=', $filters['max_usage_per_user_from']);
        }

        if (isset($filters['max_usage_per_user_to'])) {
            $query->where('max_usage_per_user', '<=', $filters['max_usage_per_user_to']);
        }

        if (isset($filters['unlimited_usage'])) {
            if ($filters['unlimited_usage']) {
                $query->whereNull('max_usage_per_user');
            } else {
                $query->whereNotNull('max_usage_per_user');
            }
        }

        if (isset($filters['start_date_from'])) {
            $query->where('start_date', '>=', $filters['start_date_from']);
        }

        if (isset($filters['start_date_to'])) {
            $query->where('start_date', '<=', $filters['start_date_to']);
        }

        if (isset($filters['expire_date_from'])) {
            $query->where('expire_date', '>=', $filters['expire_date_from']);
        }

        if (isset($filters['expire_date_to'])) {
            $query->where('expire_date', '<=', $filters['expire_date_to']);
        }

        if (isset($filters['status'])) {
            $now = now()->toDateString();
            
            switch ($filters['status']) {
                case 'valid':
                    $query->where('is_active', true)
                          ->where('start_date', '<=', $now)
                          ->where('expire_date', '>=', $now);
                    break;
                case 'expired':
                    $query->where('expire_date', '<', $now);
                    break;
                case 'upcoming':
                    $query->where('start_date', '>', $now);
                    break;
                case 'running':
                    $query->where('start_date', '<=', $now)
                          ->where('expire_date', '>=', $now);
                    break;
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
            }
        }

        if (isset($filters['high_value'])) {
            $threshold = $filters['high_value'];
            $query->where(function ($q) use ($threshold) {
                $q->where(function ($subQ) use ($threshold) {
                    $subQ->where('discount_type', 'fixed')
                         ->where('discount_amount', '>=', $threshold);
                })->orWhere(function ($subQ) {
                    $subQ->where('discount_type', 'percentage')
                         ->where('discount_amount', '>=', 50);
                });
            });
        }

        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        if (isset($filters['created_in_last_days'])) {
            $query->where('created_at', '>=', now()->subDays($filters['created_in_last_days']));
        }

        if (isset($filters['expiring_in_next_days'])) {
            $query->whereBetween('expire_date', [
                now()->toDateString(),
                now()->addDays($filters['expiring_in_next_days'])->toDateString()
            ]);
        }

        $coupons = $query->get();

        $filename = 'coupons_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(
            new CouponExport($coupons),
            $filename,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    /**
     * Export coupons to CSV
     */
    public function exportToCsv(array $couponIds = null, array $filters = []): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $query = Coupon::with(['company', 'customer']);

        // Apply same filters as Excel export
        if ($couponIds) {
            $query->whereIn('id', $couponIds);
        }

        if (isset($filters['title'])) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }

        if (isset($filters['code'])) {
            $query->where('code', 'like', '%' . $filters['code'] . '%');
        }

        if (isset($filters['coupon_type'])) {
            $query->where('coupon_type', $filters['coupon_type']);
        }

        if (isset($filters['coupon_types'])) {
            $query->whereIn('coupon_type', $filters['coupon_types']);
        }

        if (isset($filters['discount_type'])) {
            $query->where('discount_type', $filters['discount_type']);
        }

        if (isset($filters['discount_types'])) {
            $query->whereIn('discount_type', $filters['discount_types']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['company_ids'])) {
            $query->whereIn('company_id', $filters['company_ids']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['customer_ids'])) {
            $query->whereIn('customer_id', $filters['customer_ids']);
        }

        if (isset($filters['has_customer'])) {
            if ($filters['has_customer']) {
                $query->whereNotNull('customer_id');
            } else {
                $query->whereNull('customer_id');
            }
        }

        if (isset($filters['discount_amount_from'])) {
            $query->where('discount_amount', '>=', $filters['discount_amount_from']);
        }

        if (isset($filters['discount_amount_to'])) {
            $query->where('discount_amount', '<=', $filters['discount_amount_to']);
        }

        if (isset($filters['min_purchase_from'])) {
            $query->where('min_purchase', '>=', $filters['min_purchase_from']);
        }

        if (isset($filters['min_purchase_to'])) {
            $query->where('min_purchase', '<=', $filters['min_purchase_to']);
        }

        if (isset($filters['max_discount_from'])) {
            $query->where('max_discount', '>=', $filters['max_discount_from']);
        }

        if (isset($filters['max_discount_to'])) {
            $query->where('max_discount', '<=', $filters['max_discount_to']);
        }

        if (isset($filters['max_usage_per_user'])) {
            $query->where('max_usage_per_user', $filters['max_usage_per_user']);
        }

        if (isset($filters['max_usage_per_user_from'])) {
            $query->where('max_usage_per_user', '>=', $filters['max_usage_per_user_from']);
        }

        if (isset($filters['max_usage_per_user_to'])) {
            $query->where('max_usage_per_user', '<=', $filters['max_usage_per_user_to']);
        }

        if (isset($filters['unlimited_usage'])) {
            if ($filters['unlimited_usage']) {
                $query->whereNull('max_usage_per_user');
            } else {
                $query->whereNotNull('max_usage_per_user');
            }
        }

        if (isset($filters['start_date_from'])) {
            $query->where('start_date', '>=', $filters['start_date_from']);
        }

        if (isset($filters['start_date_to'])) {
            $query->where('start_date', '<=', $filters['start_date_to']);
        }

        if (isset($filters['expire_date_from'])) {
            $query->where('expire_date', '>=', $filters['expire_date_from']);
        }

        if (isset($filters['expire_date_to'])) {
            $query->where('expire_date', '<=', $filters['expire_date_to']);
        }

        if (isset($filters['status'])) {
            $now = now()->toDateString();
            
            switch ($filters['status']) {
                case 'valid':
                    $query->where('is_active', true)
                          ->where('start_date', '<=', $now)
                          ->where('expire_date', '>=', $now);
                    break;
                case 'expired':
                    $query->where('expire_date', '<', $now);
                    break;
                case 'upcoming':
                    $query->where('start_date', '>', $now);
                    break;
                case 'running':
                    $query->where('start_date', '<=', $now)
                          ->where('expire_date', '>=', $now);
                    break;
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
            }
        }

        if (isset($filters['high_value'])) {
            $threshold = $filters['high_value'];
            $query->where(function ($q) use ($threshold) {
                $q->where(function ($subQ) use ($threshold) {
                    $subQ->where('discount_type', 'fixed')
                         ->where('discount_amount', '>=', $threshold);
                })->orWhere(function ($subQ) {
                    $subQ->where('discount_type', 'percentage')
                         ->where('discount_amount', '>=', 50);
                });
            });
        }

        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        if (isset($filters['created_in_last_days'])) {
            $query->where('created_at', '>=', now()->subDays($filters['created_in_last_days']));
        }

        if (isset($filters['expiring_in_next_days'])) {
            $query->whereBetween('expire_date', [
                now()->toDateString(),
                now()->addDays($filters['expiring_in_next_days'])->toDateString()
            ]);
        }

        $coupons = $query->get();

        $filename = 'coupons_' . now()->format('Y_m_d_H_i_s') . '.csv';

        return Excel::download(
            new CouponExport($coupons),
            $filename,
            \Maatwebsite\Excel\Excel::CSV
        );
    }
}
