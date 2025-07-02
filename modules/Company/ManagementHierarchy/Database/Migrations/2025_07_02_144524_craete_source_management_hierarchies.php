<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('source_management_hierarchies', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->enum("type",["branch","management","department"]);
            $table->uuid("company_id")->index()->references("id")->constrained('companies')->cascadeOnDelete();
            $table->tinyInteger("is_active")->default(1);
            $table->timestamps();
        });
    }
};
