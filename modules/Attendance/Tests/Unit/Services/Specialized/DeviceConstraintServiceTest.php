<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services\Specialized;

use PHPUnit\Framework\TestCase;
use Modules\Attendance\Services\DeviceConstraintService;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;

class DeviceConstraintServiceTest extends TestCase
{
    private DeviceConstraintService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DeviceConstraintService();
    }

    /**
     * Test device validation with matching device ID
     */
    public function test_validate_device_constraint_matching_device(): void
    {
        // Create mock attendance with allowed device
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['device_id', 'device-123'],
            ['device_info', [
                'device_name' => 'iPhone 15',
                'os_version' => 'iOS 18.0',
                'app_version' => '1.5.0'
            ]]
        ]);
        
        // Create mock constraint with allowed device ID
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_DEVICE],
            ['subtype', AttendanceConstraint::DEVICE_WHITELIST],
            ['config', [
                'allowed_devices' => ['device-123', 'device-456', 'device-789']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateDeviceConstraint($attendance, $constraint);
        
        // No violation should be detected when device is allowed
        $this->assertFalse($result);
    }

    /**
     * Test device validation with non-matching device ID
     */
    public function test_validate_device_constraint_non_matching_device(): void
    {
        // Create mock attendance with non-allowed device
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['device_id', 'device-999'],
            ['device_info', [
                'device_name' => 'Unknown Device',
                'os_version' => 'Android 14',
                'app_version' => '1.5.0'
            ]]
        ]);
        
        // Create mock constraint with allowed device IDs
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_DEVICE],
            ['subtype', AttendanceConstraint::DEVICE_WHITELIST],
            ['config', [
                'allowed_devices' => ['device-123', 'device-456', 'device-789']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateDeviceConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::DEVICE_WHITELIST, $result['constraint_type']);
        $this->assertStringContainsString('unauthorized device', strtolower($result['message']));
        $this->assertEquals('device-999', $result['details']['device_id']);
    }

    /**
     * Test app version validation with compliant version
     */
    public function test_app_version_validation_compliant_version(): void
    {
        // Create mock attendance with compliant app version
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['device_info', [
                'device_name' => 'iPhone 15',
                'os_version' => 'iOS 18.0',
                'app_version' => '2.1.0'
            ]]
        ]);
        
        // Create mock constraint with minimum app version
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_DEVICE],
            ['subtype', AttendanceConstraint::DEVICE_APP_VERSION],
            ['config', [
                'min_app_version' => '2.0.0'
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateDeviceConstraint($attendance, $constraint);
        
        // No violation should be detected when app version is compliant
        $this->assertFalse($result);
    }

    /**
     * Test app version validation with non-compliant version
     */
    public function test_app_version_validation_non_compliant_version(): void
    {
        // Create mock attendance with outdated app version
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['device_info', [
                'device_name' => 'iPhone 15',
                'os_version' => 'iOS 18.0',
                'app_version' => '1.9.5'
            ]]
        ]);
        
        // Create mock constraint with minimum app version
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_DEVICE],
            ['subtype', AttendanceConstraint::DEVICE_APP_VERSION],
            ['config', [
                'min_app_version' => '2.0.0'
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateDeviceConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::DEVICE_APP_VERSION, $result['constraint_type']);
        $this->assertStringContainsString('app version', strtolower($result['message']));
        $this->assertEquals('1.9.5', $result['details']['current_version']);
        $this->assertEquals('2.0.0', $result['details']['required_version']);
    }

    /**
     * Test OS version validation with compliant version
     */
    public function test_os_version_validation_compliant_version(): void
    {
        // Create mock attendance with compliant OS version
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['device_info', [
                'device_name' => 'iPhone 15',
                'os_version' => 'iOS 18.0',
                'app_version' => '2.1.0'
            ]]
        ]);
        
        // Create mock constraint with minimum OS requirements
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_DEVICE],
            ['subtype', AttendanceConstraint::DEVICE_OS_VERSION],
            ['config', [
                'min_os_versions' => [
                    'iOS' => '17.0',
                    'Android' => '13.0'
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateDeviceConstraint($attendance, $constraint);
        
        // No violation should be detected when OS version is compliant
        $this->assertFalse($result);
    }

    /**
     * Test OS version validation with non-compliant version
     */
    public function test_os_version_validation_non_compliant_version(): void
    {
        // Create mock attendance with outdated OS version
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['device_info', [
                'device_name' => 'iPhone 15',
                'os_version' => 'iOS 16.5',
                'app_version' => '2.1.0'
            ]]
        ]);
        
        // Create mock constraint with minimum OS requirements
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_DEVICE],
            ['subtype', AttendanceConstraint::DEVICE_OS_VERSION],
            ['config', [
                'min_os_versions' => [
                    'iOS' => '17.0',
                    'Android' => '13.0'
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateDeviceConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::DEVICE_OS_VERSION, $result['constraint_type']);
        $this->assertStringContainsString('os version', strtolower($result['message']));
        $this->assertEquals('iOS 16.5', $result['details']['current_version']);
        $this->assertEquals('17.0', $result['details']['required_version']);
    }

    /**
     * Test device model validation with allowed model
     */
    public function test_device_model_validation_allowed_model(): void
    {
        // Create mock attendance with allowed device model
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['device_info', [
                'device_name' => 'iPhone 15 Pro',
                'device_model' => 'A2850',
                'os_version' => 'iOS 18.0',
                'app_version' => '2.1.0'
            ]]
        ]);
        
        // Create mock constraint with allowed device models
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_DEVICE],
            ['subtype', AttendanceConstraint::DEVICE_MODEL],
            ['config', [
                'allowed_models' => [
                    'iPhone' => ['A2850', 'A2849', 'A2848'],
                    'Samsung' => ['SM-S918B', 'SM-S918U']
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateDeviceConstraint($attendance, $constraint);
        
        // No violation should be detected when device model is allowed
        $this->assertFalse($result);
    }

    /**
     * Test device model validation with non-allowed model
     */
    public function test_device_model_validation_non_allowed_model(): void
    {
        // Create mock attendance with non-allowed device model
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['device_info', [
                'device_name' => 'iPhone 14',
                'device_model' => 'A2649',
                'os_version' => 'iOS 18.0',
                'app_version' => '2.1.0'
            ]]
        ]);
        
        // Create mock constraint with allowed device models
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_DEVICE],
            ['subtype', AttendanceConstraint::DEVICE_MODEL],
            ['config', [
                'allowed_models' => [
                    'iPhone' => ['A2850', 'A2849', 'A2848'],
                    'Samsung' => ['SM-S918B', 'SM-S918U']
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateDeviceConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::DEVICE_MODEL, $result['constraint_type']);
        $this->assertStringContainsString('device model', strtolower($result['message']));
        $this->assertEquals('A2649', $result['details']['device_model']);
    }
}
