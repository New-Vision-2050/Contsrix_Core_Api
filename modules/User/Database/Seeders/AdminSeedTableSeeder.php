<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Modules\User\Models\User;

class AdminSeedTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (App::environment('production') == false) {
            User::firstOrCreate(
                [
                    'name' => 'Admin',
                    'email' => 'admin@constrix-nv.com',
                    'password' => "Test1234",
                ]
            );
        }
    }
}
