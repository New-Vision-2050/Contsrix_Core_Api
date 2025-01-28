<?php

declare(strict_types=1);

namespace Modules\Company\RegistrationType\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Company\RegistrationType\Models\RegistrationType;

/** @extends Factory<RegistrationType> */
class RegistrationTypeFactory extends Factory
{
    protected $model = RegistrationType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
