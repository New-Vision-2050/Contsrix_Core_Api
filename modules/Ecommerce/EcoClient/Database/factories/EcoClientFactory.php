<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoClient\Models\EcoClient;

/** @extends Factory<EcoClient> */
class EcoClientFactory extends Factory
{
    protected $model = EcoClient::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
