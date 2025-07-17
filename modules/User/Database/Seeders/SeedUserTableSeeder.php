<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Modules\User\Models\User;
use Ranium\SeedOnce\Traits\SeedOnce;
class SeedUserTableSeeder extends Seeder
{
    use SeedOnce;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Removed dd() to allow proper seeder execution
        Model::unguard();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => Hash::make('12345678')
        ]);
        // $this->call("OthersTableSeeder");
    }
}
