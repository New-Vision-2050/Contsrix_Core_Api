<?php

namespace Modules\Program\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Program\Models\Program;
use Illuminate\Database\Eloquent\Model;

class ProgramDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $programs = ['human resources', 'companies', 'settings', 'users'];

        foreach ($programs as $programName) {
            Program::firstOrCreate(['name' => $programName]);
        }
    }
}
