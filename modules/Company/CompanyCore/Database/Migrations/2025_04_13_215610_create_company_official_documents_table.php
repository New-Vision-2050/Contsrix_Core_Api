<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_official_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description');
            $table->text('document_number');
            $table->date("start_date");
            $table->date("end_date");
            $table->date("notification_date");
            $table->uuid('company_id')->index();
            $table->uuid("document_type_id")->index();


            $table->timestamps();
        });
    }
};
