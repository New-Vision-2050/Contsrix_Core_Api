<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('management_hierarchies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum("type",["branch","management","department"])->default("branch");
<<<<<<< HEAD
            $table->foreignUuid("company_id")->index()->constrained('companies')->cascadeOnDelete();
=======
            $table->uuid("company_id")->index();
>>>>>>> 7be6c72c (merge with stage (first version ))
            $table->text('path')->nullable();

            $table->timestamps();
        });
        Schema::table('management_hierarchies', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->index()
                ->constrained('management_hierarchies')
                ->cascadeOnDelete();
        });
        DB::statement('ALTER TABLE management_hierarchies ADD INDEX management_hierarchies_path_index (`path`(191))');
    }

    public function down(): void
    {
        Schema::dropIfExists('management_hierarchies');
    }
};
