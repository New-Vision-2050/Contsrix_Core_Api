<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\SubEntity\Models\RegistrationForm;

return new class extends Migration {
    public function up()
    {
        Schema::table('sub_entities', function (Blueprint $table) {
            $table->uuid('registration_form_id')->nullable();
        });

        // Example: Assign all sub_entities to a default registration_form
        $default = RegistrationForm::firstOrCreate(
            ['slug' => 'test'],[ "name" => ['ar' => 'نموذج للاختبار', 'en' => 'Test Registration Form']]
        );

        $defaultId = $default?->id;

        DB::table('sub_entities')->update([
            'registration_form_id' => $defaultId,
        ]);

        Schema::table('sub_entities', function (Blueprint $table) {
            $table->uuid('registration_form_id')->change();
            $table->foreign('registration_form_id')->references('id')->on('registration_forms')->cascadeOnDelete();
        });
    }
};
