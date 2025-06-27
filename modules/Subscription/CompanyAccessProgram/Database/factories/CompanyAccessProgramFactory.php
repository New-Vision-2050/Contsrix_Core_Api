<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;

/** @extends Factory<CompanyAccessProgram> */
class CompanyAccessProgramFactory extends Factory
{
    protected $model = CompanyAccessProgram::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
