<?php

namespace Modules\User\Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        dd('Seeder is running 1');

        Model::unguard();
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => Hash::make('12345678')
        ]);
    }
}
