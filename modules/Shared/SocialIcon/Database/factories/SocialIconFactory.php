<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\SocialIcon\Models\SocialIcon;

/** @extends Factory<SocialIcon> */
class SocialIconFactory extends Factory
{
    protected $model = SocialIcon::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
