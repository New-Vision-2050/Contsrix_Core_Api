<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Coupon\Models\Coupon;

/** @extends Factory<Coupon> */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
