<?php

namespace Modules\Shared\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;

class FileUploadService
{
    public function uploadFile($model, $file, $filePath = 'default', string $collectionName = 'upload', string $visibility = 'public', ?string $folderId = null)
    {
        $disk = $visibility === 'public' ? 's3_public' : 's3_private';

        // Normalize to array if single file is passed
        $files = is_array($file) ? $file : [$file];

        $allMedia = collect();

        foreach ($files as $index => $singleFile) {
            // Skip empty or invalid file
            if (!$singleFile instanceof UploadedFile) {
                continue;
            }

            $fileName = sprintf(
                '%s_%s.%s',
                pathinfo($singleFile->getClientOriginalName(), PATHINFO_FILENAME),
                uniqid(),
                $singleFile->getClientOriginalExtension()
            );

            // Store file temporarily to mimic form request file input
            request()->files->set('file', [$singleFile]);

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

            $allMedia = $allMedia->merge($media);
        }

        return $allMedia;
    }
}
