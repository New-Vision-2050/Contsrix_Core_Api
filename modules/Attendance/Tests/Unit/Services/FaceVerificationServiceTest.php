<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Illuminate\Http\UploadedFile;
use Modules\Attendance\Services\FaceRecognitionService;
use Modules\Attendance\Services\FaceVerificationService;
use Modules\User\Models\User;
use PHPUnit\Framework\TestCase;

class FaceVerificationServiceTest extends TestCase
{
    public function test_verify_short_circuits_when_face_recognition_disabled(): void
    {
        $faceRecognitionService = $this->createMock(FaceRecognitionService::class);
        $faceRecognitionService->method('isEnabled')->willReturn(false);
        $faceRecognitionService->expects($this->never())->method('compareFaces');

        $service = new FaceVerificationService($faceRecognitionService);

        $user = new User();
        $photo = $this->createMock(UploadedFile::class);

        $result = $service->verify($user, $photo);

        $this->assertTrue($result['matched']);
        $this->assertSame('disabled', $result['provider']);
    }

    public function test_verify_short_circuits_for_exempt_user_even_when_enabled(): void
    {
        $faceRecognitionService = $this->createMock(FaceRecognitionService::class);
        $faceRecognitionService->method('isEnabled')->willReturn(true);
        $faceRecognitionService->expects($this->never())->method('compareFaces');

        $service = new FaceVerificationService($faceRecognitionService);

        $user = new User();
        $user->face_verification_exempt = true;
        $photo = $this->createMock(UploadedFile::class);

        $result = $service->verify($user, $photo);

        $this->assertTrue($result['matched']);
        $this->assertSame('exempt', $result['provider']);
    }

    public function test_verify_via_liveness_short_circuits_for_exempt_user(): void
    {
        $faceRecognitionService = $this->createMock(FaceRecognitionService::class);
        $faceRecognitionService->method('isEnabled')->willReturn(true);
        $faceRecognitionService->expects($this->never())->method('getLivenessSessionResult');

        $service = new FaceVerificationService($faceRecognitionService);

        $user = new User();
        $user->face_verification_exempt = true;

        $result = $service->verifyViaLiveness($user, 'session-123');

        $this->assertTrue($result['matched']);
        $this->assertSame('exempt', $result['provider']);
    }

    public function test_is_exempt_returns_false_by_default(): void
    {
        $faceRecognitionService = $this->createMock(FaceRecognitionService::class);
        $service = new FaceVerificationService($faceRecognitionService);

        $user = new User();

        $this->assertFalse($service->isExempt($user));
    }
}
