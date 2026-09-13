<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Aws\Exception\AwsException;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Exceptions\AttendanceException;

/**
 * Thin wrapper around the AWS Rekognition CompareFaces API.
 *
 * Stateless — Octane-safe singleton. Credentials and threshold come from
 * config/services.php ("rekognition" key), which reads dedicated
 * AWS_REKOGNITION_* env vars (kept separate from the AWS_* vars used for
 * S3/DigitalOcean Spaces file storage).
 */
class FaceRecognitionService
{
    private ?RekognitionClient $client = null;

    public function isEnabled(): bool
    {
        return (bool) config('services.rekognition.enabled', false);
    }

    private function client(): RekognitionClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $key = config('services.rekognition.key');
        $secret = config('services.rekognition.secret');

        if (!$key || !$secret) {
            throw AttendanceException::faceVerificationMisconfigured();
        }

        return $this->client = new RekognitionClient([
            'version' => 'latest',
            'region' => config('services.rekognition.region', 'us-east-1'),
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ],
        ]);
    }

    /**
     * Compare a source (reference/profile) image against a target (live capture) image.
     *
     * @param  string $sourceImageBytes Raw bytes of the profile/reference photo.
     * @param  string $targetImageBytes Raw bytes of the freshly captured photo.
     * @return array{matched: bool, similarity: float, threshold: float}
     * @throws AttendanceException
     */
    public function compareFaces(string $sourceImageBytes, string $targetImageBytes): array
    {
        $threshold = (float) config('services.rekognition.similarity_threshold', 80);

        try {
            $result = $this->client()->compareFaces([
                'SourceImage' => ['Bytes' => $sourceImageBytes],
                'TargetImage' => ['Bytes' => $targetImageBytes],
                // Ask AWS to only report matches above the threshold; we still
                // double-check below so we always return a similarity value.
                'SimilarityThreshold' => $threshold,
                'QualityFilter' => 'AUTO',
            ]);
        } catch (AwsException $e) {
            Log::error('Rekognition CompareFaces failed', [
                'aws_error_code' => $e->getAwsErrorCode(),
                'message' => $e->getAwsErrorMessage() ?? $e->getMessage(),
            ]);

            $code = $e->getAwsErrorCode();

            if (in_array($code, ['InvalidParameterException'], true)) {
                // AWS throws this when no face (or no comparable face) is detected
                // in either the source or the target image.
                throw AttendanceException::faceNotDetected();
            }

            throw AttendanceException::faceVerificationFailed($e->getAwsErrorMessage() ?? $e->getMessage());
        }

        $matches = $result['FaceMatches'] ?? [];

        if (empty($matches)) {
            return [
                'matched' => false,
                'similarity' => 0.0,
                'threshold' => $threshold,
            ];
        }

        // Highest-confidence match.
        usort($matches, static fn (array $a, array $b) => ($b['Similarity'] ?? 0) <=> ($a['Similarity'] ?? 0));
        $best = $matches[0];

        $similarity = (float) ($best['Similarity'] ?? 0);

        return [
            'matched' => $similarity >= $threshold,
            'similarity' => $similarity,
            'threshold' => $threshold,
        ];
    }

    /**
     * Start a new AWS Face Liveness session. The returned session ID is handed to the
     * client app, which uses an AWS Amplify/Rekognition Liveness SDK to stream a short
     * challenge-response video directly to AWS (never through our servers) for that
     * session ID.
     *
     * @throws AttendanceException
     */
    public function createLivenessSession(): string
    {
        try {
            $result = $this->client()->createFaceLivenessSession([]);
        } catch (AwsException $e) {
            Log::error('Rekognition CreateFaceLivenessSession failed', [
                'aws_error_code' => $e->getAwsErrorCode(),
                'message' => $e->getAwsErrorMessage() ?? $e->getMessage(),
            ]);

            throw AttendanceException::faceVerificationFailed($e->getAwsErrorMessage() ?? $e->getMessage());
        }

        return $result['SessionId'];
    }

    /**
     * Fetch the result of a previously created liveness session.
     *
     * @return array{status: string, confidence: float, reference_image_bytes: ?string}
     * @throws AttendanceException
     */
    public function getLivenessSessionResult(string $sessionId): array
    {
        try {
            $result = $this->client()->getFaceLivenessSessionResults([
                'SessionId' => $sessionId,
            ]);
        } catch (AwsException $e) {
            Log::error('Rekognition GetFaceLivenessSessionResults failed', [
                'aws_error_code' => $e->getAwsErrorCode(),
                'message' => $e->getAwsErrorMessage() ?? $e->getMessage(),
            ]);

            $code = $e->getAwsErrorCode();

            if (in_array($code, ['ResourceNotFoundException', 'SessionNotFoundException'], true)) {
                throw AttendanceException::livenessSessionNotFound();
            }

            throw AttendanceException::faceVerificationFailed($e->getAwsErrorMessage() ?? $e->getMessage());
        }

        $referenceImageBytes = null;
        $referenceImage = $result['ReferenceImage'] ?? null;
        if ($referenceImage && isset($referenceImage['Bytes'])) {
            $referenceImageBytes = (string) $referenceImage['Bytes'];
        }

        return [
            'status' => (string) ($result['Status'] ?? 'UNKNOWN'),
            'confidence' => (float) ($result['Confidence'] ?? 0),
            'reference_image_bytes' => $referenceImageBytes,
        ];
    }
}
