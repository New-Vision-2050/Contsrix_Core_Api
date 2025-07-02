<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('management_hierarchy_managements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('management_hierarchy_id');
            $table->unsignedBigInteger('management_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('management_hierarchy_id', 'mh_managements_mh_id_foreign')
                ->references('id')->on('management_hierarchies')->onDelete('cascade');
            $table->foreign('management_id', 'mh_managements_management_id_foreign')
                ->references('id')->on('management_hierarchies')->onDelete('cascade');

            // Indexes
            $table->index(['management_hierarchy_id', 'management_id'], 'mh_managements_composite_index');
            $table->unique(['management_hierarchy_id', 'management_id'], 'mh_managements_unique');
        });
    }
};
