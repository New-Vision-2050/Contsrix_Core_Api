<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\MedicalInsurance\Models\MedicalInsurance;

/** @extends Factory<MedicalInsurance> */
class MedicalInsuranceFactory extends Factory
{
    protected $model = MedicalInsurance::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
