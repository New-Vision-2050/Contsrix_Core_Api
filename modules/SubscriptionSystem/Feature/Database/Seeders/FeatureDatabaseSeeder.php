<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Program\Models\Program;
use Modules\SubEntity\Models\SubEntity;
use Modules\SubscriptionSystem\Feature\Models\Feature;
use Ranium\SeedOnce\Traits\SeedOnce;

class FeatureDatabaseSeeder extends Seeder
{
    use SeedOnce;

    public function run(): void
    {
        $programs = Program::get();
        $subEntities = SubEntity::get();

        if ($programs->isEmpty() || $subEntities->isEmpty()) {
            return;
        }

        foreach ($programs->take(10) as $program) {
            Feature::factory()->forFeatureable($program)->create();
        }

        foreach ($subEntities->take(10) as $subEntity) {
            Feature::factory()->forFeatureable($subEntity)->create();
        }

    }
}
