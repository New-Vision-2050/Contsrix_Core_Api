
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_work_order_excel_imports', function (Blueprint $table) {
            $table->uuid('id');

            $table->uuid('project_id');
            $table->uuid('company_id');
            $table->uuid('user_id')->nullable();

            $table->string('status', 40);
            $table->string('original_filename')->nullable();
            $table->string('stored_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('updated_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);

            $table->json('row_errors')->nullable();
            $table->json('row_warnings')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index(['company_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_work_order_excel_imports');
    }
};
