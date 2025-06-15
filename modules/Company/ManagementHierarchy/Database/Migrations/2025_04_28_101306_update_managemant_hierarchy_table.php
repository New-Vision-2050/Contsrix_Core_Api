<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('management_hierarchies', function (Blueprint $table) {
<<<<<<< HEAD
//             $table->dropColumn("id");
//             $table->id()->first();
=======
            $table->dropColumn("id");
            $table->id()->first();
>>>>>>> 7be6c72c (merge with stage (first version ))
        });
    }
};
