<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Country\Models\Country;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $sql = File::get(database_path('sql/countries.sql'));
        DB::unprepared($sql);
        Schema::table('countries', function ($table) {
            $table->boolean('status')->default(0);
            $table->uuid('sms_driver_id')->nullable();
        });
        Country::query()
            ->where("name", "Egypt")
            ->orWhere("name", "Saudi Arabia")
            ->update(["status" => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
