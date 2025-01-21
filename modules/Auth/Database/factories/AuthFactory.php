<?php

declare(strict_types=1);

namespace Modules\Auth\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\Auth;

/** @extends Factory<Auth> */
class AuthFactory extends Factory
{
    protected $model = Auth::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
