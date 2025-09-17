<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\EcoProduct\Models\ProductDetail;

/** @extends Factory<EcoProduct> */
class ProductDetailFactory extends Factory
{
    protected $model = ProductDetail::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
