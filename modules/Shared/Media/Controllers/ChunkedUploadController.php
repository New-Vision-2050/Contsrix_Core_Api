<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Shared\Media\Requests\InitiateChunkedUploadRequest;
use Modules\Shared\Media\Requests\UploadChunkRequest;
use Modules\Shared\Media\Services\ChunkedUploadService;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ChunkedUploadController extends Controller
{
    public function __construct(
        private ChunkedUploadService $service,
    ) {
    }

    /**
     * Start a resumable upload session. Returns an upload_id that identifies
     * this session for subsequent chunk uploads.
     */
    public function initiate(InitiateChunkedUploadRequest $request): JsonResponse
    {
        try {
            $metadata = $this->service->initiate(
                $request->string('file_name')->toString(),
                (int) $request->input('file_size'),
                (int) $request->input('total_chunks'),
                $request->input('mime_type'),
                (string) tenant('id'),
                (string) Auth::id(),
            );

            return Json::item($metadata);
        } catch (HttpExceptionInterface $e) {
            return Json::error($e->getMessage(), $e->getStatusCode(), httpStatus: $e->getStatusCode());
        }
    }

    /**
     * Upload a single chunk. Safe to retry the same chunk_index.
     */
    public function uploadChunk(UploadChunkRequest $request, string $uploadId): JsonResponse
    {
        try {
            $metadata = $this->service->storeChunk(
                $uploadId,
                (int) $request->input('chunk_index'),
                $request->file('chunk'),
                (string) tenant('id'),
            );

            return Json::item($metadata);
        } catch (HttpExceptionInterface $e) {
            return Json::error($e->getMessage(), $e->getStatusCode(), httpStatus: $e->getStatusCode());
        }
    }

    /**
     * Ask which chunks were already received, to resume after a network
     * drop or page reload.
     */
    public function status(string $uploadId): JsonResponse
    {
        try {
            $metadata = $this->service->status($uploadId, (string) tenant('id'));

            return Json::item($metadata);
        } catch (HttpExceptionInterface $e) {
            return Json::error($e->getMessage(), $e->getStatusCode(), httpStatus: $e->getStatusCode());
        }
    }

    /**
     * Merge all chunks into the final file. The returned upload_id becomes a
     * short-lived, single-use token to be sent to the consuming endpoint
     * (e.g. replace-media, attachment-requests create) instead of a raw file.
     */
    public function complete(string $uploadId): JsonResponse
    {
        try {
            $metadata = $this->service->complete($uploadId, (string) tenant('id'));

            return Json::item([
                'upload_id' => $metadata['upload_id'],
                'file_name' => $metadata['file_name'],
                'file_size' => $metadata['file_size'],
                'mime_type' => $metadata['mime_type'],
            ]);
        } catch (HttpExceptionInterface $e) {
            return Json::error($e->getMessage(), $e->getStatusCode(), httpStatus: $e->getStatusCode());
        }
    }

    /**
     * Cancel an in-progress upload and clean up its temp chunks.
     */
    public function abort(string $uploadId): JsonResponse
    {
        $this->service->abort($uploadId, (string) tenant('id'));

        return Json::deleted();
    }
}
