<?php

declare(strict_types=1);

namespace Modules\UserInfo\JobOffer\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\JobOffer\Models\JobOffer;

/** @extends Factory<JobOffer> */
class JobOfferFactory extends Factory
{
    protected $model = JobOffer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
