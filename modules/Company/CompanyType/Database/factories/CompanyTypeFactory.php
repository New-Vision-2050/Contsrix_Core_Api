<?php

declare(strict_types=1);

namespace Modules\Company\CompanyType\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Company\CompanyType\Models\CompanyType;

/** @extends Factory<CompanyType> */
class CompanyTypeFactory extends Factory
{
    protected $model = CompanyType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
