<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteIcon\Models\WebsiteIcon;

/** @extends Factory<WebsiteIcon> */
class WebsiteIconFactory extends Factory
{
    protected $model = WebsiteIcon::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
