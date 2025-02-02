<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationType\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;

/** @extends Factory<CompanyRegistrationType> */
class CompanyRegistrationTypeFactory extends Factory
{
    protected $model = CompanyRegistrationType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
