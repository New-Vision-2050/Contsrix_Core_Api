<?php

declare(strict_types=1);

namespace Modules\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;
use Illuminate\Database\Eloquent\Model;
use Modules\Subscription\Models\Module;

class ModuleDatabaseSeeder extends Seeder
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

        $modules = [
            [
                'name' => ['en' => 'companies', 'ar' => 'الشركات'],
                'slug' => 'companies',
            ],
            [
                'name' => ['en' => 'human-resources', 'ar' => 'الموارد البشرية'],
                'slug' => 'human-resources',
            ],
            [
                'name' => ['en' => 'settings', 'ar' => 'الإعدادت'],
                'slug' => 'settings',
            ],
            [
                'name' => ['en' => 'users', 'ar' => 'المستخدمين'],
                'slug' => 'users',
            ],
        ];


        foreach ($modules as $module) {
            Module::firstOrCreate(
                ['name' => $module['name'], 'slug' => $module['slug']]
            );
        }
    }
}
