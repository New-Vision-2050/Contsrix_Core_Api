<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AdminRequest\Models\AdminRequest;

/** @extends Factory<AdminRequest> */
class AdminRequestFactory extends Factory
{
    protected $model = AdminRequest::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
