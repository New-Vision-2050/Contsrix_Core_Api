<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactInfo\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteContactInfo\Models\WebsiteContactInfo;

/** @extends Factory<WebsiteContactInfo> */
class WebsiteContactInfoFactory extends Factory
{
    protected $model = WebsiteContactInfo::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
