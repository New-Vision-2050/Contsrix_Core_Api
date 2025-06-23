<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SubscriptionSystem\Modules\Models\Module;

/** @extends Factory<Modules> */
class ModulesFactory extends Factory
{
    protected $model = Module::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
