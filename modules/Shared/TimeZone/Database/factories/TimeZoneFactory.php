<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\TimeZone\Models\TimeZone;

/** @extends Factory<TimeZone> */
class TimeZoneFactory extends Factory
{
    protected $model = TimeZone::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
