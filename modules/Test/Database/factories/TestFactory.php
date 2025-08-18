<?php

declare(strict_types=1);

namespace Modules\Test\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Test\Models\Test;

/** @extends Factory<Test> */
class TestFactory extends Factory
{
    protected $model = Test::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
