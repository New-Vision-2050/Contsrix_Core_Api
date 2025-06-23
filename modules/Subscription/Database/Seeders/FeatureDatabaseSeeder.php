<?php

declare(strict_types=1);

namespace Modules\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;
use Illuminate\Database\Eloquent\Model;
use Modules\Subscription\Models\Module;

class FeatureDatabaseSeeder extends Seeder
{
    use SeedOnce;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $featuresByModules = [
            'users' => [
                [
                    'name' => ['en' => 'Create User', 'ar' => 'إنشاء مستخدم'],
                    'slug' => 'create-user',
                ],
            ]
        ];


        foreach ($featuresByModules as $moduleSlug => $features) {
            $module = Module::where('slug', $moduleSlug)->firstOrFail('id');
            $module->features()->createMany($features);
        }
    }
}
