<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteCouponRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
