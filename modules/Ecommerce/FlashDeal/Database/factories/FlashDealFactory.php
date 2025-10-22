<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\FlashDeal\Models\FlashDeal;

/** @extends Factory<FlashDeal> */
class FlashDealFactory extends Factory
{
    protected $model = FlashDeal::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
