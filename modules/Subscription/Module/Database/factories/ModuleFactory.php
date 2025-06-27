<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Subscription\Module\Models\Module;

/** @extends Factory<Module> */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
