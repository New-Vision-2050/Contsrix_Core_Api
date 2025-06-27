<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('managements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('management_hierarchy_id')->unique()->constrained('management_hierarchies')->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('path')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_code')->nullable();
            $table->string('email')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_main')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('managements');
    }
};
