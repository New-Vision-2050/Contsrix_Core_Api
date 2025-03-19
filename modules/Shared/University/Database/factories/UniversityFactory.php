<?php

declare(strict_types=1);

namespace Modules\Shared\University\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\University\Models\University;

/** @extends Factory<University> */
class UniversityFactory extends Factory
{
    protected $model = University::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
