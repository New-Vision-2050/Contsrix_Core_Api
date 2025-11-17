<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Models\CategoryWebsiteCMS;

/** @extends Factory<CategoryWebsiteCMS> */
class CategoryWebsiteCMSFactory extends Factory
{
    protected $model = CategoryWebsiteCMS::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
