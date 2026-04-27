<?php

namespace Modules\Shared\Media\Services;

use Illuminate\Http\UploadedFile;

class FileUploadService
{
    /**
     * Prefer S3/Minio when buckets are configured; otherwise local disks so
     * Spatie does not build AwsS3V3Adapter with a null bucket (e.g. local / seeding).
     */
    private function resolveStorageDisk(string $visibility): string
    {
        if ($visibility === 'public') {
            $bucket = config('filesystems.disks.s3_public.bucket');

            return (is_string($bucket) && $bucket !== '') ? 's3_public' : 'public';
        }

        $bucket = config('filesystems.disks.s3_private.bucket');

        return (is_string($bucket) && $bucket !== '') ? 's3_private' : 'local';
    }

    public function uploadFile(
         $model,
        UploadedFile|array $file,
        string $filePath = 'default',
        string $collectionName = 'upload',
        string $visibility = 'public',
        ?string $folderId = null,
        ?string $fileId = null,

    ) {
        $disk = $this->resolveStorageDisk($visibility);

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
                    'file_id'=>$fileId,
                    'file_path' => $filePath,
                    'disk' => $disk,
                ])
                ->preservingOriginal()
                ->toMediaCollection($collectionName, $disk);
            $media->file_id = $fileId;
            $media->save();

            $allMedia->push($media);
        }

        return $allMedia;
    }
}
