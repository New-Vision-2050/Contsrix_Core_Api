<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Observers;

use Modules\Shared\Media\Models\CustomMedia;
use Modules\ArchiveLibrary\File\Models\File;
use Illuminate\Support\Facades\Log;

class CustomMediaObserver
{
    /**
     * Handle the CustomMedia "deleting" event.
     * Automatically delete associated File if file_id exists.
     *
     * @param CustomMedia $media
     * @return void
     */
    public function deleting(CustomMedia $media): void
    {
        try {
            // Check if media has a file_id
            if ($media->file_id) {
                // Find and delete the associated File
                $file = File::find($media->file_id);
                
                if ($file) {
                    Log::info("Deleting File associated with Media", [
                        'media_id' => $media->id,
                        'file_id' => $media->file_id,
                        'file_name' => $file->name ?? 'N/A'
                    ]);
                    
                    $file->delete();
                    
                    Log::info("Successfully deleted File associated with Media", [
                        'file_id' => $media->file_id
                    ]);
                } else {
                    Log::warning("File not found for deletion", [
                        'media_id' => $media->id,
                        'file_id' => $media->file_id
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to delete File associated with Media", [
                'media_id' => $media->id,
                'file_id' => $media->file_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw exception to prevent media deletion if file deletion fails
            throw $e;
        }
    }

    /**
     * Handle the CustomMedia "deleted" event.
     * Log successful media deletion.
     *
     * @param CustomMedia $media
     * @return void
     */
    public function deleted(CustomMedia $media): void
    {
        if ($media->file_id) {
            Log::info("Media with file_id successfully deleted", [
                'media_id' => $media->id,
                'file_id' => $media->file_id
            ]);
        }
    }
}
