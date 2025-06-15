<?php

namespace Modules\Shared\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;

class FileUploadService
{
    public function uploadFile(
         $model,
        UploadedFile|array $file,
        string $filePath = 'default',
        string $collectionName = 'upload',
        string $visibility = 'public',
        ?string $folderId = null
    ) {
        $disk = $visibility === 'public' ? 's3_public' : 's3_private';

        if (empty($file)) {
            return collect();
        }
        // Normalize to array
        $files = is_array($file) ? $file : [$file];


        $allMedia = collect();

        foreach ($files as $singleFile) {
            if (!$singleFile instanceof UploadedFile) {
                continue;
            }

            $fileName = sprintf(
                '%s_%s.%s',
                pathinfo($singleFile->getClientOriginalName(), PATHINFO_FILENAME),
                uniqid(),
                $singleFile->getClientOriginalExtension()
            );

            $media = $model->addMedia($singleFile)
                ->usingFileName($fileName)
                ->storingConversionsOnDisk($disk)
                ->withCustomProperties([
                    'folder_id' => $folderId,
                    'file_path' => $filePath,
                    'disk' => $disk,
                ])
                ->preservingOriginal()
                ->toMediaCollection($collectionName, $disk);

            $allMedia->push($media);
        }

        return $allMedia;
    }
}
