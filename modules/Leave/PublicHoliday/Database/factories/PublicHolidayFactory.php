<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Leave\PublicHoliday\Models\PublicHoliday;

/** @extends Factory<PublicHoliday> */
class PublicHolidayFactory extends Factory
{
    protected $model = PublicHoliday::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
