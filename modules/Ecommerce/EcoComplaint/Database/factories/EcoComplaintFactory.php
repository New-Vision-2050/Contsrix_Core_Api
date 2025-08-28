<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoComplaint\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoComplaint\Models\EcoComplaint;

/** @extends Factory<EcoComplaint> */
class EcoComplaintFactory extends Factory
{
    protected $model = EcoComplaint::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
