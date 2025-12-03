<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteTheme\Models\WebsiteTheme;

/** @extends Factory<WebsiteTheme> */
class WebsiteThemeFactory extends Factory
{
    protected $model = WebsiteTheme::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
