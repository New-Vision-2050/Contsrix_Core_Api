<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Banner\Models\Banner;

/** @extends Factory<Banner> */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
