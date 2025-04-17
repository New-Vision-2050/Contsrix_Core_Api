<?php

namespace Modules\Shared\TypePrivilege\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\TypePrivilege\Models\TypePrivilege;
use Ranium\SeedOnce\Traits\SeedOnce;

class TypePrivilegeSeederTableSeeder extends Seeder
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

        $typePrivileges = [
            ['ar' => 'فردي', 'en' => 'Individual'],
            ['ar' => 'عائلي', 'en' => 'Family'],
        ];

        foreach ($typePrivileges as $index => $item) {
            TypePrivilege::firstOrCreate(
                ['id' => $index + 1],
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']]]
            );
        }
    }
}
