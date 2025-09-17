<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoLanguage\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoLanguage\Models\EcoLanguage;

/** @extends Factory<EcoLanguage> */
class EcoLanguageFactory extends Factory
{
    protected $model = EcoLanguage::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
