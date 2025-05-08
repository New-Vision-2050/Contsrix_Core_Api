<?php

declare(strict_types=1);

namespace Modules\SubEntity\Database\Seeders;

use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;
use Modules\SubEntity\Models\SubEntity;

class SubEntityDatabaseSeeder extends Seeder
{
    use SeedOnce;
    public function run(): void
    {
        SubEntity::factory()
            ->count(50)
            ->create();
    }
}
