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
            $table->uuid('program_id');
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $table->timestamps();
        });
    }
};
