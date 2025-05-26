<?php

namespace Modules\Shared\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;

class FileDeletedService
{
    public function deleteFile($model, $inputFiles, string $collectionName = 'upload')
    {

        if (!$inputFiles ) {
            return;
        }

        // Get the IDs of the existing media to keep (those sent from the frontend)
        $fieldIds = collect($inputFiles)
            ->pluck('id')
            ->filter()
            ->toArray();

        // Delete media not included in the request for this specific field
        $existingMedia = $model->getMedia($collectionName);
        foreach ($existingMedia as $media) {
            if (!in_array($media->id, $fieldIds)) {
                $media->delete();
            }
        }



    }
}
