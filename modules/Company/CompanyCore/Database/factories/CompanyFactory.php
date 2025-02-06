<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Company\CompanyCore\Models\Company;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
