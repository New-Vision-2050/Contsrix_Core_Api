<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Laravel\Telescope\Watchers\FetchesStackTrace;
use Modules\Program\Models\Program;
use Modules\SubEntity\Models\SubEntity;
use Modules\SubscriptionSystem\Feature\Models\Feature;
use Ranium\SeedOnce\Traits\SeedOnce;

class FeatureFake4DatabaseSeeder extends Seeder
{
    use SeedOnce;

    public function run(): void
    {
         Feature::query()->delete();
        $programs = Program::get();
        $subEntities = SubEntity::get();

        foreach ($programs as $program) {
            Feature::create([
                'id' => Str::uuid(),
                'name' => [
                    'en' => 'Program Feature ' . $program->id,
                    'ar' => 'ميزة برنامج ' . $program->id,
                ],
                'slug' => Str::slug('Program Feature ' . $program->id),
                'featureable_type' => Program::class,
                'featureable_id' => $program->id,
            ]);
        }

        foreach ($subEntities as $subEntity) {
            Feature::create([
                'id' => Str::uuid(),
                'name' => [
                    'en' => 'SubEntity Feature ' . $subEntity->id,
                    'ar' => 'ميزة كيان فرعي ' . $subEntity->id,
                ],
                'slug' => Str::slug('SubEntity Feature ' . $subEntity->id),
                'featureable_type' => SubEntity::class,
                'featureable_id' => $subEntity->id,
            ]);
        }
    }
}
