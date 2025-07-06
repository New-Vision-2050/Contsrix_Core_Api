<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Models\Attendance;
use Modules\User\Models\User;
use Modules\Company\Models\Company;

echo "=== ATTENDANCE CONSTRAINTS INTEGRATION TEST ===\n\n";

// Test 1: Database Setup
echo "1. Testing database setup...\n";
try {
    // Check if tables exist
    $constraintsCount = AttendanceConstraint::count();
    $attendanceCount = Attendance::count();
    echo "✅ Database tables accessible\n";
    echo "   - Constraints: $constraintsCount\n";
    echo "   - Attendance records: $attendanceCount\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 2: Create Test Data
echo "\n2. Creating test data...\n";
try {
    $company = Company::factory()->create(['name' => 'Test Company']);
    $user = User::factory()->create(['company_id' => $company->id]);
    echo "✅ Test company and user created\n";
} catch (Exception $e) {
    echo "❌ Test data creation failed: " . $e->getMessage() . "\n";
}

// Test 3: Constraint Creation
echo "\n3. Testing constraint creation...\n";
try {
    $constraint = AttendanceConstraint::create([
        'company_id' => $company->id,
        'constraint_type' => AttendanceConstraint::TYPE_LOCATION,
        'constraint_name' => AttendanceConstraint::LOCATION_GEOFENCING,
        'constraint_config' => [
            'allowed_zones' => [
                [
                    'name' => 'Office',
                    'latitude' => 40.7128,
                    'longitude' => -74.0060,
                    'radius' => 100
                ]
            ]
        ],
        'is_active' => true,
        'priority' => 1,
        'blocking' => true,
        'created_by' => $user->id,
        'updated_by' => $user->id
    ]);
    echo "✅ Geofencing constraint created: {$constraint->id}\n";
} catch (Exception $e) {
    echo "❌ Constraint creation failed: " . $e->getMessage() . "\n";
}

// Test 4: Run Feature Tests
echo "\n4. Running PHPUnit feature tests...\n";
$testCommand = "cd " . __DIR__ . " && php artisan test modules/Attendance/Tests/Feature/AttendanceConstraintsIntegrationTest.php --verbose";
$output = shell_exec($testCommand);
echo $output ?: "Test execution completed\n";

echo "\n=== INTEGRATION TEST COMPLETED ===\n";
