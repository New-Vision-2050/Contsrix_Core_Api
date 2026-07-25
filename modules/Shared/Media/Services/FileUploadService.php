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
        string|array|null $fileId = null,

    ) {
        $disk = $this->resolveStorageDisk($visibility);

        if (empty($file)) {
            return collect();
        }
        // Normalize to array
        $files = is_array($file) ? $file : [$file];

        // Normalize file IDs: accept a single ID or an array of IDs keyed by the
        // same keys as $file, so mapping stays correct even with non-sequential
        // or sparse keys (e.g. when some entries were filtered out upstream).
        $fileIds = is_array($fileId) ? $fileId : ($fileId !== null ? [$fileId] : []);

        $allMedia = collect();

        foreach ($files as $index => $singleFile) {
            if (!$singleFile instanceof UploadedFile) {
                continue;
            }

            $currentFileId = $fileIds[$index] ?? null;

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
                    'file_id'=>$currentFileId,
                    'file_path' => $filePath,
                    'disk' => $disk,
                ])
                ->preservingOriginal()
                ->toMediaCollection($collectionName, $disk);
            $media->file_id = $currentFileId;
            $media->save();

            $allMedia->push($media);
        }

        return $allMedia;
    }
}
