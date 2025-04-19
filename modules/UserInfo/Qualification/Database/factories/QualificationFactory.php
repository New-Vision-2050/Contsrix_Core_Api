<?php

declare(strict_types=1);

namespace Modules\UserInfo\Qualification\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\Qualification\Models\Qualification;

/** @extends Factory<Qualification> */
class QualificationFactory extends Factory
{
    protected $model = Qualification::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
