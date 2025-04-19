<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Country\Models\Country;


class CitiesTableSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $sql = File::get(database_path('sql/countries.sql'));
            DB::unprepared($sql);
            Schema::table('countries', function ($table) {
                $table->boolean('status')->defualt(0);
                $table->uuid('sms_driver_id')->nullable();
            });
            Country::query()
                ->where("name", "Egypt")
                ->orWhere("name", "Saudi Arabia")
                ->update(["status" => 1]);
            $sql = File::get(database_path('sql/states.sql'));
            DB::unprepared($sql);
            $sql = File::get(database_path('sql/cities.sql'));
            DB::unprepared($sql);
        } catch (\Exception $e) {
            dd(substr($e->getMessage(), 0, 500));
        }
    }
}
