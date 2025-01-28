<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;

/** @extends Factory<CompanyRegistrationForm> */
class CompanyRegistrationFormFactory extends Factory
{
    protected $model = CompanyRegistrationForm::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
