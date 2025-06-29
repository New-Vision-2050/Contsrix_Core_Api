<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Database\Seeder;

use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;
use Illuminate\Database\Eloquent\Model;
use Modules\Subscription\Module\Models\Module;

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
                'children' => [
                    [
                        'name' => ['en' => 'clients', 'ar' => 'العملاء'],
                        'slug' => 'clients',
                    ],
                    [
                        'name' => ['en' => 'employees', 'ar' => 'الموظفين'],
                        'slug' => 'employees',
                    ],
                    [
                        'name' => ['en' => 'brokers', 'ar' => 'الوسطاء'],
                        'slug' => 'brokers',
                    ],
                ],
            ],
        ];

       $this->createModules($modules);
    }

    /**
     * Recursively create modules and their children.
     *
     * @param array $modules
     * @param string|null $parentId
     * @return void
     */
    protected function createModules(array $modules, ?string $parentId = null): void
    {
        foreach ($modules as $moduleData) {
            $children = $moduleData['children'] ?? [];
            unset($moduleData['children']);

            $module = Module::firstOrCreate(
                ['slug' => $moduleData['slug']],
                [
                    'name' => $moduleData['name'],
                    'module_id' => $parentId,
                ]
            );

            if (!empty($children)) {
                $this->createModules($children, $module->id);
            }
        }
    }
}
