<?php

namespace Modules\Shared\Media\Services;

use Illuminate\Support\Facades\Config;

class FileUploadService
{

    public function uploadFile($model, $file, $filePath = 'default', string $collectionName = 'upload', string $visibility = 'public', ?string $folderId = null)
    {
        $disk = $visibility === 'public' ? 's3_public' : 's3_private';

        // Generate a unique file name
        $fileName = sprintf(
            '%s_%s.%s',
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            uniqid(),
            $file->getClientOriginalExtension()
        );

        // Temporarily store the file in the request to let addMultipleMediaFromRequest() find it
        request()->files->set('file', [$file]);

        $media = $model->addMultipleMediaFromRequest(['file'])->each(function ($fileAdder) use ($folderId, $filePath, $disk, $collectionName, $fileName) {
            $fileAdder
                ->usingFileName($fileName)
                ->storingConversionsOnDisk($disk)
                ->withCustomProperties([
                    'folder_id' => $folderId,
                    'file_path' => $filePath,
                    'disk' => $disk,
                ])
                ->preservingOriginal()
                ->toMediaCollection($collectionName, $disk);
        });

        return $media;
    }


}
