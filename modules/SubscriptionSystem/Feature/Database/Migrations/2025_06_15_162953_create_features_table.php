<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug');
            // Module relation (nullable, UUID)
            $table->uuid('module_id');
            $table->foreign('module_id')->references('id')->on('modules')->cascadeOnDelete();
            $table->timestamps();
        });
    }
};
