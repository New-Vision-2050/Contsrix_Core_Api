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
}
