<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Project\ProjectManagement\Models\AttachmentRequestItem;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('files')
            || ! Schema::hasTable('attachment_request_items')
            || ! Schema::hasColumn('files', 'source_model_type')
            || ! Schema::hasColumn('files', 'source_model_id')) {
            return;
        }

        DB::table('files')
            ->join(
                'attachment_request_items',
                'attachment_request_items.id',
                '=',
                'files.source_model_id'
            )
            ->where('files.source_model_type', AttachmentRequestItem::class)
            ->whereNotNull('attachment_request_items.file_name')
            ->select([
                'files.id as id',
                'files.name as archive_name',
                'attachment_request_items.file_name as source_file_name',
            ])
            ->orderBy('files.id')
            ->chunkById(100, function ($files): void {
                foreach ($files as $file) {
                    $documentCode = pathinfo((string) $file->source_file_name, PATHINFO_FILENAME);

                    if ($documentCode === '' || $documentCode === $file->archive_name) {
                        continue;
                    }

                    DB::table('files')
                        ->where('id', $file->id)
                        ->update(['name' => $documentCode]);
                }
            }, 'files.id', 'id');
    }

    public function down(): void
    {
        // The original storage suffix cannot be reconstructed safely.
    }
};
