<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Models\WebsiteTermAndCondition;

/** @extends Factory<WebsiteTermAndCondition> */
class WebsiteTermAndConditionFactory extends Factory
{
    protected $model = WebsiteTermAndCondition::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
