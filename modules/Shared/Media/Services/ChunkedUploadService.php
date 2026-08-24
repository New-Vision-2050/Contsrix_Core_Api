<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Generic resumable/chunked upload handler.
 *
 * Flow:
 *  1. initiate()   -> client gets an upload_id
 *  2. storeChunk() -> client PUTs/POSTs each chunk, can retry/resume any chunk
 *  3. status()     -> client asks which chunks are already received (resume support)
 *  4. complete()   -> server merges all chunks into one file and returns a
 *                     short-lived "upload_id" token
 *  5. Consumer endpoints (e.g. replaceMedia, createRequest) call
 *     resolveCompletedUpload() to exchange the token for a real UploadedFile
 *     instance, exactly like a normal multipart upload. The token is single-use
 *     and expires automatically.
 */
class ChunkedUploadService
{
    private const DISK = 'local';

    private const BASE_PATH = 'chunked-uploads';

    private const PENDING_TTL_SECONDS = 60 * 60 * 24; // 24h to finish uploading chunks

    private const COMPLETED_TTL_SECONDS = 60 * 60 * 2; // 2h to consume the merged file

    public function initiate(
        string $fileName,
        int $fileSize,
        int $totalChunks,
        ?string $mimeType,
        string $ownerCompanyId,
        string $ownerUserId,
    ): array {
        $uploadId = (string) Str::uuid();

        $metadata = [
            'upload_id' => $uploadId,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'total_chunks' => $totalChunks,
            'received_chunks' => [],
            'owner_company_id' => $ownerCompanyId,
            'owner_user_id' => $ownerUserId,
            'status' => 'pending',
        ];

        Storage::disk(self::DISK)->makeDirectory($this->chunkDir($uploadId));
        Cache::put($this->cacheKey($uploadId), $metadata, self::PENDING_TTL_SECONDS);

        return $metadata;
    }

    public function storeChunk(
        string $uploadId,
        int $chunkIndex,
        UploadedFile $chunk,
        string $ownerCompanyId,
    ): array {
        $metadata = $this->findPendingMetadataOrFail($uploadId, $ownerCompanyId);

        if ($chunkIndex < 0 || $chunkIndex >= (int) $metadata['total_chunks']) {
            throw new HttpException(422, 'Invalid chunk index for this upload.');
        }

        Storage::disk(self::DISK)->putFileAs(
            $this->chunkDir($uploadId),
            $chunk,
            $this->chunkFileName($chunkIndex)
        );

        $received = $metadata['received_chunks'];
        if (! in_array($chunkIndex, $received, true)) {
            $received[] = $chunkIndex;
            sort($received);
        }
        $metadata['received_chunks'] = $received;

        Cache::put($this->cacheKey($uploadId), $metadata, self::PENDING_TTL_SECONDS);

        return $metadata;
    }

    public function status(string $uploadId, string $ownerCompanyId): array
    {
        return $this->findPendingMetadataOrFail($uploadId, $ownerCompanyId);
    }

    public function complete(string $uploadId, string $ownerCompanyId): array
    {
        $metadata = $this->findPendingMetadataOrFail($uploadId, $ownerCompanyId);

        $totalChunks = (int) $metadata['total_chunks'];
        if (count($metadata['received_chunks']) !== $totalChunks) {
            throw new HttpException(422, 'Not all chunks have been uploaded yet.');
        }

        $disk = Storage::disk(self::DISK);
        $mergedRelativePath = $this->chunkDir($uploadId) . '/merged';
        $mergedAbsolutePath = $disk->path($mergedRelativePath);

        $out = fopen($mergedAbsolutePath, 'wb');
        if ($out === false) {
            throw new HttpException(500, 'Unable to assemble uploaded file.');
        }

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $disk->path($this->chunkDir($uploadId) . '/' . $this->chunkFileName($i));
            $in = fopen($chunkPath, 'rb');
            if ($in === false) {
                fclose($out);
                throw new HttpException(500, "Missing chunk {$i} while assembling file.");
            }
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        // Remove the individual chunk files now that they are merged.
        for ($i = 0; $i < $totalChunks; $i++) {
            $disk->delete($this->chunkDir($uploadId) . '/' . $this->chunkFileName($i));
        }

        $metadata['status'] = 'completed';
        $metadata['merged_path'] = $mergedRelativePath;
        Cache::put($this->cacheKey($uploadId), $metadata, self::COMPLETED_TTL_SECONDS);

        return $metadata;
    }

    public function abort(string $uploadId, string $ownerCompanyId): void
    {
        $metadata = Cache::get($this->cacheKey($uploadId));
        if (is_array($metadata) && (string) $metadata['owner_company_id'] === $ownerCompanyId) {
            Storage::disk(self::DISK)->deleteDirectory($this->chunkDir($uploadId));
            Cache::forget($this->cacheKey($uploadId));
        }
    }

    /**
     * Exchange a completed upload token for a real UploadedFile instance,
     * usable anywhere a normal multipart file upload is expected.
     * Single-use: the token and its temp file are removed after this call.
     */
    public function resolveCompletedUpload(string $uploadId, string $ownerCompanyId): UploadedFile
    {
        $metadata = Cache::get($this->cacheKey($uploadId));

        if (! is_array($metadata) || ($metadata['status'] ?? null) !== 'completed') {
            throw new HttpException(422, 'Upload is not completed or has expired.');
        }

        if ((string) $metadata['owner_company_id'] !== $ownerCompanyId) {
            throw new HttpException(403, 'You are not allowed to use this upload.');
        }

        $disk = Storage::disk(self::DISK);
        $absolutePath = $disk->path($metadata['merged_path']);

        if (! is_file($absolutePath)) {
            throw new HttpException(422, 'Uploaded file could not be found. Please upload again.');
        }

        $uploadedFile = new UploadedFile(
            $absolutePath,
            $metadata['file_name'],
            $metadata['mime_type'],
            null,
            true // test mode: allow constructing from a non-HTTP-uploaded path
        );

        // Single-use token: the cache entry is removed immediately so it can't
        // be replayed. The temp merged file itself is left on disk (the
        // consumer still needs to read it after this call, e.g. to store it
        // via the media library) and is later removed by the scheduled
        // `chunked-uploads:cleanup` command, which is Octane-worker safe
        // unlike register_shutdown_function.
        Cache::forget($this->cacheKey($uploadId));

        return $uploadedFile;
    }

    /**
     * Delete temp chunk/merged directories older than the given age. Intended
     * to be run periodically (see routes/console.php schedule) to reclaim
     * disk space from expired or already-consumed uploads.
     */
    public function cleanupExpired(int $olderThanHours = 24): int
    {
        $disk = Storage::disk(self::DISK);
        $cutoff = now()->subHours($olderThanHours)->getTimestamp();
        $removed = 0;

        foreach ($disk->directories(self::BASE_PATH) as $directory) {
            $absoluteDir = $disk->path($directory);
            $mtime = @filemtime($absoluteDir) ?: 0;

            if ($mtime <= $cutoff) {
                $disk->deleteDirectory($directory);
                $removed++;
            }
        }

        return $removed;
    }

    private function findPendingMetadataOrFail(string $uploadId, string $ownerCompanyId): array
    {
        $metadata = Cache::get($this->cacheKey($uploadId));

        if (! is_array($metadata)) {
            throw new HttpException(404, 'Upload session not found or expired.');
        }

        if ((string) $metadata['owner_company_id'] !== $ownerCompanyId) {
            throw new HttpException(403, 'You are not allowed to access this upload.');
        }

        return $metadata;
    }

    private function cacheKey(string $uploadId): string
    {
        return "chunked_upload:{$uploadId}";
    }

    private function chunkDir(string $uploadId): string
    {
        return self::BASE_PATH . '/' . $uploadId;
    }

    private function chunkFileName(int $index): string
    {
        return "chunk_{$index}";
    }
}
