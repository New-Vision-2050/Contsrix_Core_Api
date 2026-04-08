<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('resource_shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Polymorphic relationship to shareable resource
            $table->uuidMorphs('shareable');
            
            // Company that owns the resource
            $table->uuid('owner_company_id')->index();
            
            // Company that resource is shared with
            $table->uuid('shared_with_company_id')->index();
            
            // Status: pending, accepted, rejected
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            
            // Schema IDs that are shared (JSON array)
            $table->json('schema_ids')->nullable();
            
            // Who initiated the share
            $table->uuid('shared_by_user_id')->nullable();
            
            // Who accepted/rejected
            $table->uuid('responded_by_user_id')->nullable();
            $table->timestamp('responded_at')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Ensure unique sharing per resource and company
            $table->unique(['shareable_type', 'shareable_id', 'shared_with_company_id'], 'unique_resource_share');
            
            // Foreign keys
            $table->foreign('owner_company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('shared_with_company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('resource_shares');
    }
};
