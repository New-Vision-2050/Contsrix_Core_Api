<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\User\Models\User;

/**
 * Verifies that a freshly captured clock-in/clock-out photo belongs to the
 * same person as the employee's stored profile photo (CompanyUser "upload_user"
 * media collection), using AWS Rekognition CompareFaces.
 *
 * Stateless — Octane-safe singleton.
 */
final class FaceVerificationService
{
    public function __construct(
        private readonly FaceRecognitionService $faceRecognitionService,
    ) {}

    /**
     * @return array{matched: bool, similarity: float, threshold: float, provider: string}
     * @throws AttendanceException
     */
    public function verify(User $user, UploadedFile $capturedPhoto): array
    {
        if (!$this->faceRecognitionService->isEnabled()) {
            return [
                'matched' => true,
                'similarity' => null,
                'threshold' => null,
                'provider' => 'disabled',
            ];
        }

        $companyUser = $user->companyUser;
        $profileMedia = $companyUser?->getFirstMedia('upload_user');

        if (!$profileMedia) {
            throw AttendanceException::noProfilePhoto();
        }

        try {
            $profileImageBytes = Storage::disk($profileMedia->disk)->get($profileMedia->getPathRelativeToRoot());
        } catch (\Throwable) {
            $profileImageBytes = null;
        }

        if (!$profileImageBytes) {
            throw AttendanceException::noProfilePhoto();
        }

        $capturedImageBytes = file_get_contents($capturedPhoto->getRealPath());

        $result = $this->faceRecognitionService->compareFaces($profileImageBytes, $capturedImageBytes);

        if (!$result['matched']) {
            throw AttendanceException::faceNotMatched($result['similarity']);
        }

        return [
            'matched' => true,
            'similarity' => $result['similarity'],
            'threshold' => $result['threshold'],
            'provider' => 'aws_rekognition',
        ];
    }
}
