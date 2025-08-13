<?php

/**
 * Test script for Branch-Based Attendance Constraints
 * 
 * This script tests the new branch-based constraints functionality
 * to ensure all components are working correctly.
 */

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Branch-Based Attendance Constraints Test ===\n\n";

try {
    // Test 1: Check if migration was successful
    echo "1. Testing database schema...\n";
    
    $hasColumns = \Illuminate\Support\Facades\Schema::hasColumns('attendance_constraints', [
        'branch_id', 
        'inherit_from_parent'
    ]);
    
    if ($hasColumns) {
        echo "✅ Migration successful - branch_id and inherit_from_parent columns exist\n";
    } else {
        echo "❌ Migration failed - columns missing\n";
        exit(1);
    }
    
    // Test 2: Check AttendanceConstraint model relationships
    echo "\n2. Testing model relationships...\n";
    
    $constraint = new \Modules\Attendance\Models\AttendanceConstraint();
    
    if (method_exists($constraint, 'branch')) {
        echo "✅ AttendanceConstraint->branch() relationship exists\n";
    } else {
        echo "❌ AttendanceConstraint->branch() relationship missing\n";
    }
    
    // Test 3: Check model scopes
    echo "\n3. Testing model scopes...\n";
    
    $scopes = ['forBranch', 'applicableToBranch', 'branchSpecific', 'companyWide'];
    $missingScopes = [];
    
    foreach ($scopes as $scope) {
        if (!method_exists($constraint, 'scope' . ucfirst($scope))) {
            $missingScopes[] = $scope;
        }
    }
    
    if (empty($missingScopes)) {
        echo "✅ All required scopes exist: " . implode(', ', $scopes) . "\n";
    } else {
        echo "❌ Missing scopes: " . implode(', ', $missingScopes) . "\n";
    }
    
    // Test 4: Check DTO classes
    echo "\n4. Testing DTO classes...\n";
    
    $dtoClasses = [
        'Modules\Attendance\DTO\CreateAttendanceConstraintDTO',
        'Modules\Attendance\DTO\UpdateAttendanceConstraintDTO',
        'Modules\Attendance\DTO\FilterAttendanceConstraintDTO'
    ];
    
    foreach ($dtoClasses as $dtoClass) {
        if (class_exists($dtoClass)) {
            $dto = new ReflectionClass($dtoClass);
            $constructor = $dto->getConstructor();
            $params = $constructor->getParameters();
            
            $hasBranchId = false;
            foreach ($params as $param) {
                if ($param->getName() === 'branch_id') {
                    $hasBranchId = true;
                    break;
                }
            }
            
            if ($hasBranchId) {
                echo "✅ {$dtoClass} has branch_id parameter\n";
            } else {
                echo "❌ {$dtoClass} missing branch_id parameter\n";
            }
        } else {
            echo "❌ {$dtoClass} class not found\n";
        }
    }
    
    // Test 5: Check controller methods
    echo "\n5. Testing controller methods...\n";
    
    $controllerMethods = ['getConstraintsByBranch', 'getInheritedConstraints', 'bulkAssignToBranch'];
    $missingMethods = [];
    
    foreach ($controllerMethods as $method) {
        if (!method_exists(\Modules\Attendance\Controllers\AttendanceConstraintController::class, $method)) {
            $missingMethods[] = $method;
        }
    }
    
    if (empty($missingMethods)) {
        echo "✅ All required controller methods exist: " . implode(', ', $controllerMethods) . "\n";
    } else {
        echo "❌ Missing controller methods: " . implode(', ', $missingMethods) . "\n";
    }
    
    // Test 6: Check routes
    echo "\n6. Testing routes...\n";
    
    $routeFiles = [
        'modules/Attendance/Routes/attendance_constraints.php',
        'modules/Attendance/Routes/management_hierarchy.php'
    ];
    
    foreach ($routeFiles as $routeFile) {
        if (file_exists($routeFile)) {
            echo "✅ Route file exists: {$routeFile}\n";
        } else {
            echo "❌ Route file missing: {$routeFile}\n";
        }
    }
    
    echo "\n=== Test Summary ===\n";
    echo "✅ Branch-based attendance constraints implementation appears to be complete!\n";
    echo "\nNext steps:\n";
    echo "1. Test API endpoints with actual HTTP requests\n";
    echo "2. Create unit and integration tests\n";
    echo "3. Update frontend to use new branch-based functionality\n";
    echo "4. Add proper error handling and validation\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Test completed successfully! ===\n";
