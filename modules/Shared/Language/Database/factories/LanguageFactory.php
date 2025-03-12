<?php

declare(strict_types=1);

namespace Modules\Shared\Language\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Language\Models\Language;

/** @extends Factory<Language> */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
