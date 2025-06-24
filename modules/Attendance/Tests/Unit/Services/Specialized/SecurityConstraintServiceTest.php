<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services\Specialized;

use PHPUnit\Framework\TestCase;
use Modules\Attendance\Services\SecurityConstraintService;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;

class SecurityConstraintServiceTest extends TestCase
{
    private SecurityConstraintService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SecurityConstraintService();
    }

    /**
     * Test two-factor authentication validation with compliant authentication
     */
    public function test_two_factor_auth_validation_compliant(): void
    {
        // Create mock attendance with 2FA completed
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['security_data', [
                'two_factor_completed' => true,
                'two_factor_method' => 'sms',
                'two_factor_timestamp' => time() - 60 // 1 minute ago
            ]]
        ]);
        
        // Create constraint requiring 2FA
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_SECURITY],
            ['subtype', AttendanceConstraint::SECURITY_TWO_FACTOR],
            ['config', [
                'require_two_factor' => true,
                'allowed_methods' => ['sms', 'app']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateSecurityConstraint($attendance, $constraint);
        
        // No violation should be detected when 2FA is properly completed
        $this->assertFalse($result);
    }

    /**
     * Test two-factor authentication validation with missing authentication
     */
    public function test_two_factor_auth_validation_missing(): void
    {
        // Create mock attendance without 2FA
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['security_data', [
                'two_factor_completed' => false
            ]]
        ]);
        
        // Create constraint requiring 2FA
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_SECURITY],
            ['subtype', AttendanceConstraint::SECURITY_TWO_FACTOR],
            ['config', [
                'require_two_factor' => true,
                'allowed_methods' => ['sms', 'app']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateSecurityConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::SECURITY_TWO_FACTOR, $result['constraint_type']);
        $this->assertStringContainsString('two-factor', strtolower($result['message']));
        $this->assertFalse($result['details']['two_factor_completed']);
    }

    /**
     * Test biometric authentication validation with compliant authentication
     */
    public function test_biometric_auth_validation_compliant(): void
    {
        // Create mock attendance with biometric authentication
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['security_data', [
                'biometric_verified' => true,
                'biometric_type' => 'fingerprint',
                'biometric_timestamp' => time() - 60 // 1 minute ago
            ]]
        ]);
        
        // Create constraint requiring biometric authentication
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_SECURITY],
            ['subtype', AttendanceConstraint::SECURITY_BIOMETRIC],
            ['config', [
                'require_biometric' => true,
                'allowed_types' => ['fingerprint', 'face_id']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateSecurityConstraint($attendance, $constraint);
        
        // No violation should be detected when biometric auth is properly completed
        $this->assertFalse($result);
    }

    /**
     * Test biometric authentication validation with missing authentication
     */
    public function test_biometric_auth_validation_missing(): void
    {
        // Create mock attendance without biometric authentication
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['security_data', [
                'biometric_verified' => false
            ]]
        ]);
        
        // Create constraint requiring biometric authentication
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_SECURITY],
            ['subtype', AttendanceConstraint::SECURITY_BIOMETRIC],
            ['config', [
                'require_biometric' => true,
                'allowed_types' => ['fingerprint', 'face_id']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateSecurityConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::SECURITY_BIOMETRIC, $result['constraint_type']);
        $this->assertStringContainsString('biometric', strtolower($result['message']));
        $this->assertFalse($result['details']['biometric_verified']);
    }

    /**
     * Test audit trail validation with compliant audit data
     */
    public function test_audit_trail_validation_compliant(): void
    {
        // Create mock attendance with complete audit trail
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['ip_address', '192.168.1.100'],
            ['device_id', 'device-123'],
            ['device_info', [
                'device_name' => 'iPhone 15',
                'os_version' => 'iOS 18.0'
            ]],
            ['location', [
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'accuracy' => 10.5
            ]]
        ]);
        
        // Create constraint requiring complete audit trail
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_SECURITY],
            ['subtype', AttendanceConstraint::SECURITY_AUDIT_TRAIL],
            ['config', [
                'required_fields' => ['ip_address', 'device_id', 'device_info', 'location']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateSecurityConstraint($attendance, $constraint);
        
        // No violation should be detected when audit trail is complete
        $this->assertFalse($result);
    }

    /**
     * Test audit trail validation with incomplete audit data
     */
    public function test_audit_trail_validation_incomplete(): void
    {
        // Create mock attendance with incomplete audit trail (missing location)
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['ip_address', '192.168.1.100'],
            ['device_id', 'device-123'],
            ['device_info', [
                'device_name' => 'iPhone 15',
                'os_version' => 'iOS 18.0'
            ]],
            ['location', null] // Missing location data
        ]);
        
        // Create constraint requiring complete audit trail
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_SECURITY],
            ['subtype', AttendanceConstraint::SECURITY_AUDIT_TRAIL],
            ['config', [
                'required_fields' => ['ip_address', 'device_id', 'device_info', 'location']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateSecurityConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::SECURITY_AUDIT_TRAIL, $result['constraint_type']);
        $this->assertStringContainsString('audit trail', strtolower($result['message']));
        $this->assertArrayHasKey('missing_fields', $result['details']);
        $this->assertContains('location', $result['details']['missing_fields']);
    }

    /**
     * Test fraud detection validation with normal attendance
     */
    public function test_fraud_detection_validation_normal(): void
    {
        // Mock previous attendance
        $previousAttendance = $this->createMock(Attendance::class);
        $previousAttendance->method('__get')->willReturnMap([
            ['clock_out', '17:00'],
            ['date', '2023-06-22'],
            ['location', [
                'latitude' => 40.7128, 
                'longitude' => -74.0060
            ]]
        ]);
        
        // Create mock for attendance repository to return previous attendance
        $attendanceRepo = $this->createMock(\Modules\Attendance\Repositories\AttendanceRepositoryInterface::class);
        $attendanceRepo->method('getUserPreviousAttendance')->willReturn($previousAttendance);
        
        // Set the repository in the service using reflection
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('attendanceRepository');
        $property->setAccessible(true);
        $property->setValue($this->service, $attendanceRepo);
        
        // Create current attendance (same location, next day)
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['clock_in', '09:00'],
            ['date', '2023-06-23'],
            ['location', [
                'latitude' => 40.7128, 
                'longitude' => -74.0060
            ]]
        ]);
        
        // Create constraint checking for suspicious activity
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_SECURITY],
            ['subtype', AttendanceConstraint::SECURITY_FRAUD_DETECTION],
            ['config', [
                'max_travel_speed_kmh' => 1000, // km/h
                'check_impossible_travel' => true
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateSecurityConstraint($attendance, $constraint);
        
        // No violation should be detected for normal attendance
        $this->assertFalse($result);
    }

    /**
     * Test fraud detection validation with suspicious activity
     */
    public function test_fraud_detection_validation_suspicious(): void
    {
        // Mock previous attendance (Los Angeles)
        $previousAttendance = $this->createMock(Attendance::class);
        $previousAttendance->method('__get')->willReturnMap([
            ['clock_out', '17:00'],
            ['date', '2023-06-23'], // Same day
            ['location', [
                'latitude' => 34.0522, 
                'longitude' => -118.2437
            ]]
        ]);
        
        // Create mock for attendance repository to return previous attendance
        $attendanceRepo = $this->createMock(\Modules\Attendance\Repositories\AttendanceRepositoryInterface::class);
        $attendanceRepo->method('getUserPreviousAttendance')->willReturn($previousAttendance);
        
        // Set the repository in the service using reflection
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('attendanceRepository');
        $property->setAccessible(true);
        $property->setValue($this->service, $attendanceRepo);
        
        // Create current attendance (New York, same day - impossible travel)
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['clock_in', '19:00'], // 2 hours later
            ['date', '2023-06-23'], // Same day
            ['location', [
                'latitude' => 40.7128, 
                'longitude' => -74.0060
            ]]
        ]);
        
        // Create constraint checking for suspicious activity
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_SECURITY],
            ['subtype', AttendanceConstraint::SECURITY_FRAUD_DETECTION],
            ['config', [
                'max_travel_speed_kmh' => 1000, // km/h
                'check_impossible_travel' => true
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateSecurityConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::SECURITY_FRAUD_DETECTION, $result['constraint_type']);
        $this->assertStringContainsString('suspicious', strtolower($result['message']));
        $this->assertArrayHasKey('travel_speed', $result['details']);
        $this->assertArrayHasKey('max_allowed_speed', $result['details']);
    }

    /**
     * Test data encryption validation with compliant encryption
     */
    public function test_data_encryption_validation_compliant(): void
    {
        // Create mock attendance with encryption flags
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['security_data', [
                'encrypted' => true,
                'encryption_method' => 'AES-256',
                'data_encrypted_at' => time() - 60
            ]]
        ]);
        
        // Create constraint requiring data encryption
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_SECURITY],
            ['subtype', AttendanceConstraint::SECURITY_DATA_ENCRYPTION],
            ['config', [
                'require_encryption' => true,
                'required_methods' => ['AES-256', 'RSA']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateSecurityConstraint($attendance, $constraint);
        
        // No violation should be detected when data is properly encrypted
        $this->assertFalse($result);
    }

    /**
     * Test data encryption validation with missing encryption
     */
    public function test_data_encryption_validation_missing(): void
    {
        // Create mock attendance without encryption
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['security_data', [
                'encrypted' => false
            ]]
        ]);
        
        // Create constraint requiring data encryption
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_SECURITY],
            ['subtype', AttendanceConstraint::SECURITY_DATA_ENCRYPTION],
            ['config', [
                'require_encryption' => true,
                'required_methods' => ['AES-256', 'RSA']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateSecurityConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::SECURITY_DATA_ENCRYPTION, $result['constraint_type']);
        $this->assertStringContainsString('encryption', strtolower($result['message']));
        $this->assertFalse($result['details']['encrypted']);
    }
}
