<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Project\TermServices\Models\TermServices;

/** @extends Factory<TermServices> */
class TermServicesFactory extends Factory
{
    protected $model = TermServices::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
