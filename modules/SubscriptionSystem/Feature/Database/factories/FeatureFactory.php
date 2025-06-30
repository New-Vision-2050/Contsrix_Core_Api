<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\SubscriptionSystem\Feature\Models\Feature;
use Modules\SubEntity\Models\SubEntity;
use Modules\Program\Models\Program;

/** @extends Factory<Feature> */
class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function definition(): array
    {
        $nameEn = $this->faker->unique()->words(2, true);
        $nameAr = $this->faker->unique()->word();

        $types = [Program::class, SubEntity::class];

        do {
            $type = $this->faker->randomElement($types);
            $id = $type::inRandomOrder()->value('id');
        } while (!$id); // استمر المحاولة حتى تجد سجل صالح

        return [
            'id' => $this->faker->uuid(),
            'name' => [
                'ar' => $nameAr,
                'en' => $nameEn,
            ],
            'slug' => Str::slug($nameEn),
            'featureable_type' => $type,
            'featureable_id' => $id,
        ];
    }

}
