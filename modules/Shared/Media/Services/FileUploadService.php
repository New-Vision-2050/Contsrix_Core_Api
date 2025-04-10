<?php

namespace Modules\Shared\Media\Services;

use Illuminate\Support\Facades\Config;

class FileUploadService
{

    public function uploadFile($model, $file, $filePath = 'default', string $collectionName = 'upload', string $visibility = 'public', ?string $folderId = null, bool $preserveOriginal = false)
    {
        // $disk = Config::get('filesystems.default');
        $disk =$visibility == 'public' ? "s3_public" : "s3_private";

        // Generate a unique file name
        $fileName = sprintf(
            '%s_%s.%s',
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            uniqid(),
            $file->getClientOriginalExtension()
        );

        // Create media builder
        $mediaBuilder = $model->addMedia($file)
            ->usingFileName($fileName)
            ->storingConversionsOnDisk($disk)
            ->withCustomProperties([
                'folder_id' => $folderId,
                'file_path' => $filePath,
                'disk' => $disk,
            ]);

        // Preserve original file if requested

            $mediaBuilder->preservingOriginal();

        // Add to media collection
        $media = $mediaBuilder->toMediaCollection($collectionName, $disk);

        return $media;

    }


}
