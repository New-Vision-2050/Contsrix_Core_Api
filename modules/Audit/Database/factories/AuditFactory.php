<?php

declare(strict_types=1);

namespace Modules\Audit\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Audit\Models\Audit;

/** @extends Factory<Audit> */
class AuditFactory extends Factory
{
    protected $model = Audit::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
