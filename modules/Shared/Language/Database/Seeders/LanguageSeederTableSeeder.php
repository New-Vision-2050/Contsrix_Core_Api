<?php

namespace Modules\Shared\Language\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Language\Models\Language;
use Ranium\SeedOnce\Traits\SeedOnce;

class LanguageSeederTableSeeder extends Seeder
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

        Language::create([
            'name' => 'اللغة العربية',
            'short_name' => 'ar',
        ]);

        Language::create([
            'name' => 'English',
            'short_name' => 'en',
        ]);

    }

}
