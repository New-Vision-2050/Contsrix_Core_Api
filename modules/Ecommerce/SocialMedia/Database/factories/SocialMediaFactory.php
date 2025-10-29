<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\SocialMedia\Models\SocialMedia;

/** @extends Factory<SocialMedia> */
class SocialMediaFactory extends Factory
{
    protected $model = SocialMedia::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
