<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Dashboard\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Dashboard\Models\Dashboard;

/** @extends Factory<Dashboard> */
class DashboardFactory extends Factory
{
    protected $model = Dashboard::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
