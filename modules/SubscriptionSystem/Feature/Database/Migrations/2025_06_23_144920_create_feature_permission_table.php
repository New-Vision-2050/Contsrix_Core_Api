<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('feature_permission', function (Blueprint $table) {
            $table->id();
            $table->uuid('feature_id');
            $table->uuid('permission_id');
            
            $table->foreign('feature_id')->references('id')->on('features')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            
            // Make the combination of feature_id and permission_id unique
            $table->unique(['feature_id', 'permission_id']);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('feature_permission');
    }
};
