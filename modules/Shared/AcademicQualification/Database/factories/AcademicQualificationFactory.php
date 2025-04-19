<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicQualification\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\AcademicQualification\Models\AcademicQualification;

/** @extends Factory<AcademicQualification> */
class AcademicQualificationFactory extends Factory
{
    protected $model = AcademicQualification::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
