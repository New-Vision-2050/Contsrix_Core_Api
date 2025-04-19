<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;

/** @extends Factory<AcademicSpecialization> */
class AcademicSpecializationFactory extends Factory
{
    protected $model = AcademicSpecialization::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
