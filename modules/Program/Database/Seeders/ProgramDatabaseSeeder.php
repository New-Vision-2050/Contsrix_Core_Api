<?php

namespace Modules\Program\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Program\Models\Program;
use Ranium\SeedOnce\Traits\SeedOnce;
use Illuminate\Database\Eloquent\Model;

class ProgramDatabaseSeeder extends Seeder
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

        $programs = [
            ['en' => 'companies', 'ar' => 'الشركات'],
            ['en' => 'human-resources', 'ar' => 'الموارد البشرية'],
            ['en' => 'settings', 'ar' => 'الإعدادت'],
            ['en' => 'users', 'ar' => 'المستخدمين'],
        ];

        foreach ($programs as $programName) {
            Program::firstOrCreate (
                ['name' => $programName]
            );
        }
    }
}
