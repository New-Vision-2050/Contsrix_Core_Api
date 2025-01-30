<?php

declare(strict_types=1);

namespace Modules\Company\CompanyField\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Company\CompanyField\Models\CompanyField;

/** @extends Factory<CompanyField> */
class CompanyFieldFactory extends Factory
{
    protected $model = CompanyField::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
