<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::table('management_hierarchies', function (Blueprint $table) {
<<<<<<< HEAD
//             $table->dropColumn("parent_id");
//             $table->unsignedBigInteger("parent_id")->nullable()->after("path");
=======
            $table->dropColumn("parent_id");
            $table->unsignedBigInteger("parent_id")->nullable()->after("path");
>>>>>>> 7be6c72c (merge with stage (first version ))
        });
    }
};
