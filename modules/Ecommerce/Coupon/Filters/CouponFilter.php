<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;
use Carbon\Carbon;

class CouponFilter extends SearchModelFilter
{
    public $relations = ['company', 'customer'];

    /**
     * Filter by coupon title
     */
    public function title($title)
    {
        return $this->where('title', 'like', '%' . $title . '%');
    }

    /**
     * Filter by coupon code
     */
    public function code($code)
    {
        return $this->where('code', 'like', '%' . $code . '%');
    }

    /**
     * General search filter (searches in title, code)
     */
    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
            $query->where('title', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%')
                  ->orWhere('coupon_type', 'like', '%' . $search . '%')
                  ->orWhere('discount_type', 'like', '%' . $search . '%');
        });
    }

    /**
     * Filter by coupon type
     */
    public function couponType($type)
    {
        return $this->where('coupon_type', $type);
    }

    /**
     * Filter by multiple coupon types
     */
    public function couponTypes($types)
    {
        if (is_array($types)) {
            return $this->whereIn('coupon_type', $types);
        }
        return $this->where('coupon_type', $types);
    }

    /**
     * Filter by discount type
     */
    public function discountType($type)
    {
        return $this->where('discount_type', $type);
    }

    /**
     * Filter by multiple discount types
     */
    public function discountTypes($types)
    {
        if (is_array($types)) {
            return $this->whereIn('discount_type', $types);
        }
        return $this->where('discount_type', $types);
    }

    /**
     * Filter by active status
     */
    public function isActive($status = true)
    {
        return $this->where('is_active', (bool) $status);
    }

    /**
     * Filter active coupons only
     */
    public function active()
    {
        return $this->where('is_active', true);
    }

    /**
     * Filter inactive coupons only
     */
    public function inactive()
    {
        return $this->where('is_active', false);
    }

    /**
     * Filter by company ID
     */
    public function companyId($companyId)
    {
        return $this->where('company_id', $companyId);
    }

    /**
     * Filter by multiple company IDs
     */
    public function companyIds($companyIds)
    {
        if (is_array($companyIds)) {
            return $this->whereIn('company_id', $companyIds);
        }
        return $this->where('company_id', $companyIds);
    }

    /**
     * Filter by customer ID
     */
    public function customerId($customerId)
    {
        return $this->where('customer_id', $customerId);
    }

    /**
     * Filter by multiple customer IDs
     */
    public function customerIds($customerIds)
    {
        if (is_array($customerIds)) {
            return $this->whereIn('customer_id', $customerIds);
        }
        return $this->where('customer_id', $customerIds);
    }

    /**
     * Filter coupons for specific customers only
     */
    public function hasCustomer($hasCustomer = true)
    {
        if ($hasCustomer) {
            return $this->whereNotNull('customer_id');
        } else {
            return $this->whereNull('customer_id');
        }
    }

    /**
     * Filter by discount amount range
     */
    public function discountAmountFrom($amount)
    {
        return $this->where('discount_amount', '>=', $amount);
    }

    public function discountAmountTo($amount)
    {
        return $this->where('discount_amount', '<=', $amount);
    }

    public function discountAmountBetween($from, $to)
    {
        return $this->whereBetween('discount_amount', [$from, $to]);
    }

    /**
     * Filter by minimum purchase amount range
     */
    public function minPurchaseFrom($amount)
    {
        return $this->where('min_purchase', '>=', $amount);
    }

    public function minPurchaseTo($amount)
    {
        return $this->where('min_purchase', '<=', $amount);
    }

    public function minPurchaseBetween($from, $to)
    {
        return $this->whereBetween('min_purchase', [$from, $to]);
    }

    /**
     * Filter by maximum discount amount range
     */
    public function maxDiscountFrom($amount)
    {
        return $this->where('max_discount', '>=', $amount);
    }

    public function maxDiscountTo($amount)
    {
        return $this->where('max_discount', '<=', $amount);
    }

    public function maxDiscountBetween($from, $to)
    {
        return $this->whereBetween('max_discount', [$from, $to]);
    }

    /**
     * Filter by maximum usage per user
     */
    public function maxUsagePerUser($usage)
    {
        return $this->where('max_usage_per_user', $usage);
    }

    public function maxUsagePerUserFrom($usage)
    {
        return $this->where('max_usage_per_user', '>=', $usage);
    }

    public function maxUsagePerUserTo($usage)
    {
        return $this->where('max_usage_per_user', '<=', $usage);
    }

    /**
     * Filter by unlimited usage (null max_usage_per_user)
     */
    public function unlimitedUsage($unlimited = true)
    {
        if ($unlimited) {
            return $this->whereNull('max_usage_per_user');
        } else {
            return $this->whereNotNull('max_usage_per_user');
        }
    }

    /**
     * Filter by start date
     */
    public function startDateFrom($date)
    {
        return $this->where('start_date', '>=', $date);
    }

    public function startDateTo($date)
    {
        return $this->where('start_date', '<=', $date);
    }

    public function startDateBetween($from, $to)
    {
        return $this->whereBetween('start_date', [$from, $to]);
    }

    /**
     * Filter by expiry date
     */
    public function expireDateFrom($date)
    {
        return $this->where('expire_date', '>=', $date);
    }

    public function expireDateTo($date)
    {
        return $this->where('expire_date', '<=', $date);
    }

    public function expireDateBetween($from, $to)
    {
        return $this->whereBetween('expire_date', [$from, $to]);
    }

    /**
     * Filter by validity status
     */
    public function valid($isValid = true)
    {
        $now = Carbon::now()->toDateString();
        
        if ($isValid) {
            return $this->where('is_active', true)
                       ->where('start_date', '<=', $now)
                       ->where('expire_date', '>=', $now);
        } else {
            return $this->where(function ($query) use ($now) {
                $query->where('is_active', false)
                      ->orWhere('start_date', '>', $now)
                      ->orWhere('expire_date', '<', $now);
            });
        }
    }

    /**
     * Filter expired coupons
     */
    public function expired($isExpired = true)
    {
        $now = Carbon::now()->toDateString();
        
        if ($isExpired) {
            return $this->where('expire_date', '<', $now);
        } else {
            return $this->where('expire_date', '>=', $now);
        }
    }

    /**
     * Filter upcoming coupons (not started yet)
     */
    public function upcoming($isUpcoming = true)
    {
        $now = Carbon::now()->toDateString();
        
        if ($isUpcoming) {
            return $this->where('start_date', '>', $now);
        } else {
            return $this->where('start_date', '<=', $now);
        }
    }

    /**
     * Filter currently running coupons
     */
    public function running($isRunning = true)
    {
        $now = Carbon::now()->toDateString();
        
        if ($isRunning) {
            return $this->where('start_date', '<=', $now)
                       ->where('expire_date', '>=', $now);
        } else {
            return $this->where(function ($query) use ($now) {
                $query->where('start_date', '>', $now)
                      ->orWhere('expire_date', '<', $now);
            });
        }
    }

    /**
     * Filter by coupon status (valid, expired, upcoming, inactive)
     */
    public function status($status)
    {
        switch ($status) {
            case 'valid':
                return $this->valid(true);
            case 'expired':
                return $this->expired(true);
            case 'upcoming':
                return $this->upcoming(true);
            case 'running':
                return $this->running(true);
            case 'inactive':
                return $this->inactive();
            case 'active':
                return $this->active();
            default:
                return $this;
        }
    }

    /**
     * Advanced search with multiple criteria
     */
    public function advancedSearch($criteria)
    {
        return $this->where(function ($query) use ($criteria) {
            if (isset($criteria['title'])) {
                $query->where('title', 'like', '%' . $criteria['title'] . '%');
            }

            if (isset($criteria['code'])) {
                $query->where('code', 'like', '%' . $criteria['code'] . '%');
            }

            if (isset($criteria['coupon_type'])) {
                $query->where('coupon_type', $criteria['coupon_type']);
            }

            if (isset($criteria['discount_type'])) {
                $query->where('discount_type', $criteria['discount_type']);
            }

            if (isset($criteria['is_active'])) {
                $query->where('is_active', (bool) $criteria['is_active']);
            }

            if (isset($criteria['company_id'])) {
                $query->where('company_id', $criteria['company_id']);
            }

            if (isset($criteria['customer_id'])) {
                $query->where('customer_id', $criteria['customer_id']);
            }

            if (isset($criteria['has_customer'])) {
                if ($criteria['has_customer']) {
                    $query->whereNotNull('customer_id');
                } else {
                    $query->whereNull('customer_id');
                }
            }

            if (isset($criteria['discount_amount_from'])) {
                $query->where('discount_amount', '>=', $criteria['discount_amount_from']);
            }

            if (isset($criteria['discount_amount_to'])) {
                $query->where('discount_amount', '<=', $criteria['discount_amount_to']);
            }

            if (isset($criteria['min_purchase_from'])) {
                $query->where('min_purchase', '>=', $criteria['min_purchase_from']);
            }

            if (isset($criteria['min_purchase_to'])) {
                $query->where('min_purchase', '<=', $criteria['min_purchase_to']);
            }

            if (isset($criteria['start_date_from'])) {
                $query->where('start_date', '>=', $criteria['start_date_from']);
            }

            if (isset($criteria['start_date_to'])) {
                $query->where('start_date', '<=', $criteria['start_date_to']);
            }

            if (isset($criteria['expire_date_from'])) {
                $query->where('expire_date', '>=', $criteria['expire_date_from']);
            }

            if (isset($criteria['expire_date_to'])) {
                $query->where('expire_date', '<=', $criteria['expire_date_to']);
            }

            if (isset($criteria['status'])) {
                $now = Carbon::now()->toDateString();
                
                switch ($criteria['status']) {
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
                    case 'inactive':
                        $query->where('is_active', false);
                        break;
                }
            }
        });
    }

    /**
     * Sort by title
     */
    public function sortByTitle($direction = 'asc')
    {
        return $this->orderBy('title', $direction);
    }

    /**
     * Sort by code
     */
    public function sortByCode($direction = 'asc')
    {
        return $this->orderBy('code', $direction);
    }

    /**
     * Sort by discount amount
     */
    public function sortByDiscountAmount($direction = 'desc')
    {
        return $this->orderBy('discount_amount', $direction);
    }

    /**
     * Sort by minimum purchase
     */
    public function sortByMinPurchase($direction = 'asc')
    {
        return $this->orderBy('min_purchase', $direction);
    }

    /**
     * Sort by start date
     */
    public function sortByStartDate($direction = 'desc')
    {
        return $this->orderBy('start_date', $direction);
    }

    /**
     * Sort by expiry date
     */
    public function sortByExpiryDate($direction = 'asc')
    {
        return $this->orderBy('expire_date', $direction);
    }

    /**
     * Sort by creation date
     */
    public function sortByCreatedAt($direction = 'desc')
    {
        return $this->orderBy('created_at', $direction);
    }

    /**
     * Filter by date range
     */
    public function dateRange($from = null, $to = null, $field = 'created_at')
    {
        if ($from && $to) {
            return $this->whereBetween($field, [$from, $to]);
        } elseif ($from) {
            return $this->where($field, '>=', $from);
        } elseif ($to) {
            return $this->where($field, '<=', $to);
        }
        return $this;
    }

    /**
     * Filter coupons created in last X days
     */
    public function createdInLastDays($days)
    {
        return $this->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Filter coupons expiring in next X days
     */
    public function expiringInNextDays($days)
    {
        return $this->whereBetween('expire_date', [
            Carbon::now()->toDateString(),
            Carbon::now()->addDays($days)->toDateString()
        ]);
    }

    /**
     * Filter by coupon value effectiveness (high value coupons)
     */
    public function highValue($threshold = 100)
    {
        return $this->where(function ($query) use ($threshold) {
            $query->where(function ($q) use ($threshold) {
                // Fixed amount coupons with high value
                $q->where('discount_type', 'fixed')
                  ->where('discount_amount', '>=', $threshold);
            })->orWhere(function ($q) {
                // Percentage coupons with high percentage (>= 50%)
                $q->where('discount_type', 'percentage')
                  ->where('discount_amount', '>=', 50);
            });
        });
    }

    /**
     * Filter free delivery coupons
     */
    public function freeDelivery()
    {
        return $this->where('coupon_type', 'free_delivery');
    }

    /**
     * Filter discount coupons
     */
    public function discountCoupons()
    {
        return $this->where('coupon_type', 'discount_on_purchase');
    }

    /**
     * Filter first order coupons
     */
    public function firstOrder()
    {
        return $this->where('coupon_type', 'first_order');
    }
}
