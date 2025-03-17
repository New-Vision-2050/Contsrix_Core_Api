<?php

namespace Modules\Shared\Media\Services;

use Illuminate\Support\Facades\Config;

class FileUploadService
{

    public function uploadFile($model, $file,$filePath ='default' ,string $collectionName = 'upload', string $visibility = 'public', ?string $folderId = null)
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

        // Store file and add custom properties
        $media = $model->addMedia($file)
            ->usingFileName($fileName)
            ->storingConversionsOnDisk($disk)
            ->withCustomProperties([
                'folder_id' => $folderId,
                'file_path' => $filePath,
                'disk'=> $disk,
            ])
            ->toMediaCollection($collectionName, $disk);

        return $media;

    }


}
