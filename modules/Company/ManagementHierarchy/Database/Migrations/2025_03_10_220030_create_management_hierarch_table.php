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
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum("type",["branch","management","department"])->default("branch");
            $table->uuid("company_id")->index();
            $table->text('path')->nullable();

            $table->timestamps();
        });
        Schema::table('management_hierarchies', function (Blueprint $table) {
            $table->uuid('parent_id')
                ->nullable()
                ->index()
                ->constrained('management_hierarchies')
                ->cascadeOnDelete();
        });
        DB::statement('ALTER TABLE management_hierarchies ADD INDEX management_hierarchies_path_index (`path`(191))');
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
