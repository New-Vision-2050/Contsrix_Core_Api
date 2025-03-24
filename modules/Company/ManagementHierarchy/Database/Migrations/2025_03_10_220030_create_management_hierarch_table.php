<?php

use Illuminate\Database\Migrations\Migration;
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
            $table->text('path')->nullable()->index();

            $table->timestamps();
        });
        Schema::table('management_hierarchies', function (Blueprint $table) {
            $table->uuid('parent_id')
                ->nullable()
                ->index()
                ->constrained('management_hierarchies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
