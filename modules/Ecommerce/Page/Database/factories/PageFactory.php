<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Page\Models\Page;

/** @extends Factory<Page> */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
