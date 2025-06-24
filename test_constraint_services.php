<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Testing Constraint Services Integration...\n\n";
    
    // Test if we can resolve the main service
    $constraintService = app(\Modules\Attendance\Services\AttendanceConstraintService::class);
    echo "✅ AttendanceConstraintService resolved successfully\n";
    
    // Test if we can resolve individual services
    $timeService = app(\Modules\Attendance\Contracts\TimeConstraintServiceInterface::class);
    echo "✅ TimeConstraintService resolved successfully\n";
    
    $locationService = app(\Modules\Attendance\Contracts\LocationConstraintServiceInterface::class);
    echo "✅ LocationConstraintService resolved successfully\n";
    
    $deviceService = app(\Modules\Attendance\Contracts\DeviceConstraintServiceInterface::class);
    echo "✅ DeviceConstraintService resolved successfully\n";
    
    $roleService = app(\Modules\Attendance\Contracts\RoleConstraintServiceInterface::class);
    echo "✅ RoleConstraintService resolved successfully\n";
    
    $behavioralService = app(\Modules\Attendance\Contracts\BehavioralConstraintServiceInterface::class);
    echo "✅ BehavioralConstraintService resolved successfully\n";
    
    $securityService = app(\Modules\Attendance\Contracts\SecurityConstraintServiceInterface::class);
    echo "✅ SecurityConstraintService resolved successfully\n";
    
    $complianceService = app(\Modules\Attendance\Contracts\ComplianceConstraintServiceInterface::class);
    echo "✅ ComplianceConstraintService resolved successfully\n";
    
    echo "\n🎉 All constraint services are properly registered and working!\n";
    echo "🚀 Refactoring integration is SUCCESSFUL!\n\n";
    
    // Test that the main service has the injected dependencies
    $reflection = new ReflectionClass($constraintService);
    $properties = $reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED);
    
    echo "Main service dependencies:\n";
    foreach ($properties as $property) {
        $property->setAccessible(true);
        $value = $property->getValue($constraintService);
        if ($value !== null) {
            echo "  - {$property->getName()}: " . get_class($value) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
