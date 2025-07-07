<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('professional_certificates', function (Blueprint $table) {


            $table->uuid('professional_bodie_id')->nullable()->change();

            $table->date('date_obtain')->nullable()->change();
            $table->date('date_end')->nullable()->change();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('professional_certificates');
    }
};
