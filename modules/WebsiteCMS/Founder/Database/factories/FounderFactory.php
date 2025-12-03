<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\Founder\Models\Founder;

/** @extends Factory<Founder> */
class FounderFactory extends Factory
{
    protected $model = Founder::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
