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
class FaceVerificationService
{
    public function __construct(
        private readonly FaceRecognitionService $faceRecognitionService,
    ) {}

    /**
     * Legacy path: verify a plain uploaded photo against the profile photo.
     * No liveness/anti-spoofing guarantee — a photo of a photo will match.
     * Prefer verifyViaLiveness() for new integrations.
     *
     * @return array{matched: bool, similarity: ?float, threshold: ?float, provider: string}
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

        $profileImageBytes = $this->getProfileImageBytes($user);
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

    /**
     * Start a new AWS Face Liveness session for the client app to run its challenge-
     * response video capture against. Returns the session ID to hand to the client.
     *
     * @throws AttendanceException
     */
    public function createLivenessSession(): array
    {
        if (!$this->faceRecognitionService->isEnabled()) {
            return ['session_id' => null, 'provider' => 'disabled'];
        }

        return [
            'session_id' => $this->faceRecognitionService->createLivenessSession(),
            'provider' => 'aws_rekognition_liveness',
        ];
    }

    /**
     * Anti-spoofing path: verify identity via a completed AWS Face Liveness session.
     * Confirms the captured frames came from a live person (not a photo/video replay
     * of the real employee) before comparing the verified reference frame against the
     * stored profile photo.
     *
     * @return array{matched: bool, similarity: ?float, threshold: ?float, liveness_confidence: ?float, provider: string}
     * @throws AttendanceException
     */
    public function verifyViaLiveness(User $user, string $sessionId): array
    {
        if (!$this->faceRecognitionService->isEnabled()) {
            return [
                'matched' => true,
                'similarity' => null,
                'threshold' => null,
                'liveness_confidence' => null,
                'provider' => 'disabled',
            ];
        }

        $session = $this->faceRecognitionService->getLivenessSessionResult($sessionId);

        match ($session['status']) {
            'SUCCEEDED' => null,
            'CREATED', 'IN_PROGRESS' => throw AttendanceException::livenessSessionNotReady(),
            'EXPIRED' => throw AttendanceException::livenessSessionExpired(),
            default => throw AttendanceException::livenessCheckFailed($session['confidence'] ?: null),
        };

        $livenessThreshold = (float) config('services.rekognition.liveness_confidence_threshold', 90);
        if ($session['confidence'] < $livenessThreshold) {
            throw AttendanceException::livenessCheckFailed($session['confidence']);
        }

        if (empty($session['reference_image_bytes'])) {
            throw AttendanceException::noLivenessReferenceImage();
        }

        $profileImageBytes = $this->getProfileImageBytes($user);

        $result = $this->faceRecognitionService->compareFaces($profileImageBytes, $session['reference_image_bytes']);

        if (!$result['matched']) {
            throw AttendanceException::faceNotMatched($result['similarity']);
        }

        return [
            'matched' => true,
            'similarity' => $result['similarity'],
            'threshold' => $result['threshold'],
            'liveness_confidence' => $session['confidence'],
            'provider' => 'aws_rekognition_liveness',
        ];
    }

    /**
     * @throws AttendanceException
     */
    private function getProfileImageBytes(User $user): string
    {
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

        return $profileImageBytes;
    }
}
