<?php

namespace Modules\Shared\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;

class FileDeletedService
{
    public function deleteFile($model, $inputFiles, string $collectionName): void
    {
        if (!$inputFiles) {
            return;
        }

        $fieldIds = [];

        if (is_array($inputFiles)) {
            if (array_is_list($inputFiles)) {
                // Array of multiple objects or IDs
                foreach ($inputFiles as $item) {
                    if (is_array($item) && isset($item['id'])) {
                        $fieldIds[] = $item['id'];
                    } elseif (is_numeric($item)) {
                        $fieldIds[] = $item;
                    }
                }
            } elseif (isset($inputFiles['id'])) {
                // Single object with ID
                $fieldIds[] = $inputFiles['id'];
            }
        } elseif (is_numeric($inputFiles)) {
            // Single ID
            $fieldIds[] = $inputFiles;
        }

        $existingMedia = $model->getMedia($collectionName);
        foreach ($existingMedia as $media) {
            if (!in_array($media->id, $fieldIds)) {
                $media->delete();
            }
        }
    }
}
