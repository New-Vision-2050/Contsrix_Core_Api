<?php

declare(strict_types=1);

namespace Modules\Shared\ProfessionalBodie\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\ProfessionalBodie\Models\ProfessionalBodie;

/** @extends Factory<ProfessionalBodie> */
class ProfessionalBodieFactory extends Factory
{
    protected $model = ProfessionalBodie::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
