<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    /**
     * Upload file to MinIO storage.
     *
     * @param Request $request
     * @param int $expiredAt (in minutes)
     * @return array
     */
    public function uploadToMinIO(Request $request, int $expiredAt = 1): array
    {
        // Get the uploaded file
        $file = $request->file('file');

        // Upload to MinIO
        $path = Storage::disk('s3')->putFileAs('uploads', $file, $file->getClientOriginalName());

        // Generate a temporary URL
        $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes($expiredAt));

        return [
            'message' => 'File uploaded successfully!',
            'path' => $path,
            'url' => $url
        ];
    }
}
