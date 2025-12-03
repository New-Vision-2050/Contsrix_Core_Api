<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\SocialMediaLink\Models\SocialMediaLink;

/** @extends Factory<SocialMediaLink> */
class SocialMediaLinkFactory extends Factory
{
    protected $model = SocialMediaLink::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
