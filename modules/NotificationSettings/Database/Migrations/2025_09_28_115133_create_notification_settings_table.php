<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_28_115133_create_notification_settings_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('user_id')->nullable(); // Optional: if notification settings are per user
            
            // Notification type: mail, sms, both
            $table->enum('type', ['mail', 'sms', 'both'])->default('mail');
            
            // Contact information (nullable)
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            
            // Reminder frequency: daily, weekly
            $table->enum('reminder_type', ['daily', 'weekly'])->default('daily');
            
            // Message content
            $table->text('message')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index('user_id');
            $table->index('type');
            $table->index('reminder_type');
            $table->index('is_active');
            
            // Unique constraint to prevent duplicates per user/company
            $table->unique(['company_id', 'user_id', 'type', 'reminder_type'], 'unique_notification_setting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
