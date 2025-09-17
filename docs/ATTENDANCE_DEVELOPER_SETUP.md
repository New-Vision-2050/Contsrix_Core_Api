# Attendance Module - Developer Setup Guide

## 🎯 Prerequisites

### System Requirements
- **PHP**: 8.2 or higher
- **Laravel**: 11.x
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Cache**: Redis 6.0+
- **Testing**: PHPUnit 11.x

### Development Tools
- **IDE**: VS Code, PhpStorm, or similar
- **API Testing**: Postman, Insomnia, or curl
- **Database**: MySQL Workbench, phpMyAdmin, or similar
- **Git**: Version control

## 🚀 Installation & Setup

### 1. Clone and Install Dependencies
```bash
# Clone the repository
git clone <repository-url>
cd Contsrix_Core_Api

# Install PHP dependencies
composer install

# Install Node dependencies (if needed)
npm install
```

### 2. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database
vim .env
```

### 3. Database Setup
```env
# .env configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=contsrix_attendance
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cache configuration
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Attendance module specific
ATTENDANCE_CACHE_TTL=3600
ATTENDANCE_MAX_PERIODS_PER_DAY=10
ATTENDANCE_MAX_WEEKLY_HOURS=168
```

### 4. Run Migrations
```bash
# Run all migrations
php artisan migrate

# Or run specific attendance migrations
php artisan migrate --path=modules/Attendance/Database/migrations
```

### 5. Seed Database (Optional)
```bash
# Seed with sample data
php artisan db:seed

# Or specific attendance seeder
php artisan db:seed --class=AttendanceSeeder
```

## 🧪 Testing Setup

### 1. Configure Test Database
```env
# .env.testing
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=contsrix_attendance_test
DB_USERNAME=test_user
DB_PASSWORD=test_password
```

### 2. Run Tests
```bash
# Run all attendance tests
./vendor/bin/phpunit tests/Unit/Attendance/

# Run specific test file
./vendor/bin/phpunit tests/Unit/Attendance/DataClasses/TimePeriodTest.php

# Run with coverage (requires Xdebug)
./vendor/bin/phpunit --coverage-html coverage/ tests/Unit/Attendance/

# Run with test documentation
./vendor/bin/phpunit tests/Unit/Attendance/DataClasses/ --testdox
```

### 3. Test Results
```
✅ 92 Unit Tests Passing
- TimePeriod: 18 tests
- DaySchedule: 21 tests  
- WeeklySchedule: 25 tests
- MultiplePeriodsConfig: 28 tests
```

## 🔧 Development Workflow

### 1. Project Structure
```
modules/Attendance/
├── Controllers/
│   ├── AttendanceController.php
│   ├── AttendanceConstraintController.php
│   └── ManagementHierarchyController.php
├── DataClasses/
│   ├── TimePeriod.php
│   ├── DaySchedule.php
│   ├── WeeklySchedule.php
│   └── MultiplePeriodsConfig.php
├── Database/
│   └── migrations/
├── Models/
│   ├── Attendance.php
│   ├── AttendanceConstraint.php
│   └── AttendanceConstraintViolation.php
├── Requests/
│   ├── CreateAttendanceConstraintRequest.php
│   └── UpdateAttendanceConstraintRequest.php
├── Services/
│   └── AttendanceConstraintService.php
└── Routes/
    ├── attendance.php
    ├── attendance_constraints.php
    └── management_hierarchy.php
```

### 2. Adding New Features

#### Step 1: Create Migration
```bash
php artisan make:migration create_new_feature_table --path=modules/Attendance/Database/migrations
```

#### Step 2: Create Model
```php
<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;

class NewFeature extends Model
{
    protected $fillable = ['name', 'config'];
    protected $casts = ['config' => 'array'];
}
```

#### Step 3: Create Request Validation
```php
<?php

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateNewFeatureRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'config' => 'required|array'
        ];
    }
}
```

#### Step 4: Create Controller
```php
<?php

namespace Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;

class NewFeatureController extends Controller
{
    public function store(CreateNewFeatureRequest $request)
    {
        // Implementation
    }
}
```

#### Step 5: Add Routes
```php
// modules/Attendance/Routes/new_feature.php
Route::middleware(['auth:api'])->group(function () {
    Route::apiResource('new-features', NewFeatureController::class);
});
```

#### Step 6: Write Tests
```php
<?php

namespace Tests\Unit\Attendance;

use PHPUnit\Framework\TestCase;

class NewFeatureTest extends TestCase
{
    public function test_can_create_new_feature()
    {
        // Test implementation
    }
}
```

### 3. Code Standards

#### PHP Standards
```php
<?php

namespace Modules\Attendance\DataClasses;

/**
 * Class documentation with description
 */
class ExampleClass
{
    /**
     * Property documentation
     */
    public readonly string $property;

    /**
     * Method documentation
     * 
     * @param string $param Parameter description
     * @return string Return description
     */
    public function exampleMethod(string $param): string
    {
        return $param;
    }
}
```

#### Testing Standards
```php
<?php

namespace Tests\Unit\Attendance\DataClasses;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_descriptive_test_name()
    {
        // Arrange
        $input = 'test data';
        
        // Act
        $result = someFunction($input);
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

## 🛠️ Debugging & Development Tools

### 1. Laravel Telescope (Optional)
```bash
# Install Telescope for debugging
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### 2. Debug Configuration
```php
// config/app.php
'debug' => env('APP_DEBUG', true),

// Enable query logging
DB::enableQueryLog();
$queries = DB::getQueryLog();
```

### 3. Logging
```php
// Use Laravel logging
Log::info('Attendance constraint validation', [
    'employee_id' => $employeeId,
    'constraint_id' => $constraintId
]);

Log::error('Constraint validation failed', [
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString()
]);
```

### 4. API Testing with Postman

#### Import Collection
1. Open Postman
2. Import the collection from `docs/postman/`
3. Set environment variables:
   - `base_url`: http://localhost:8000/api/v1
   - `jwt_token`: Your authentication token

#### Sample Requests
```json
// Create Multiple Periods Constraint
POST {{base_url}}/attendance/constraints
{
  "name": "Office Hours",
  "type": "time_multiple_periods",
  "config": {
    "weekly_schedule": {
      "monday": {
        "enabled": true,
        "periods": [
          {
            "name": "Morning",
            "start_time": "09:00",
            "end_time": "17:00",
            "spans_next_day": false,
            "grace_period_before": 15,
            "grace_period_after": 15
          }
        ]
      }
    }
  }
}
```

## 🔍 Performance Monitoring

### 1. Database Optimization
```sql
-- Add indexes for performance
CREATE INDEX idx_attendance_employee_date ON attendances(employee_id, created_at);
CREATE INDEX idx_constraints_company_type ON attendance_constraints(company_id, type);
CREATE INDEX idx_violations_status ON attendance_constraint_violations(resolution_status);
```

### 2. Cache Implementation
```php
// Cache constraint configurations
$constraints = Cache::remember("constraints.company.{$companyId}", 3600, function () use ($companyId) {
    return AttendanceConstraint::where('company_id', $companyId)->get();
});

// Cache weekly schedules
$schedule = Cache::remember("schedule.{$configId}", 1800, function () use ($config) {
    return MultiplePeriodsConfig::fromArray($config);
});
```

### 3. Performance Testing
```php
// Measure execution time
$start = microtime(true);
$result = $service->validateAttendance($attendance, $constraints);
$executionTime = microtime(true) - $start;

Log::info('Performance metric', [
    'operation' => 'validate_attendance',
    'execution_time' => $executionTime,
    'constraints_count' => count($constraints)
]);
```

## 📚 Documentation

### 1. Code Documentation
```php
/**
 * Validates attendance against multiple periods configuration
 * 
 * @param Attendance $attendance The attendance record to validate
 * @param array $config The multiple periods configuration
 * @return array|null Validation errors or null if valid
 * 
 * @throws InvalidArgumentException If configuration is invalid
 */
public function validateMultiplePeriods(Attendance $attendance, array $config): ?array
{
    // Implementation
}
```

### 2. API Documentation
- Use OpenAPI 3.0 specification
- Document all endpoints with examples
- Include error response codes
- Provide sample requests/responses

### 3. README Updates
```markdown
## Attendance Module

### Features
- Multiple periods per day scheduling
- Constraint-based validation
- Branch hierarchy support
- Comprehensive testing

### Quick Start
```php
$config = MultiplePeriodsConfig::standardOfficeHours();
```

## 🚨 Troubleshooting

### Common Issues

#### 1. Migration Errors
```bash
# Reset migrations
php artisan migrate:reset
php artisan migrate

# Check migration status
php artisan migrate:status
```

#### 2. Test Failures
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear

# Run specific failing test
./vendor/bin/phpunit tests/Unit/Attendance/DataClasses/TimePeriodTest.php::test_specific_method
```

#### 3. Performance Issues
```php
// Enable query logging to identify slow queries
DB::enableQueryLog();
// ... your code ...
$queries = DB::getQueryLog();
foreach ($queries as $query) {
    if ($query['time'] > 100) { // queries taking > 100ms
        Log::warning('Slow query detected', $query);
    }
}
```

#### 4. Memory Issues
```php
// Monitor memory usage
$memoryBefore = memory_get_usage();
// ... your code ...
$memoryAfter = memory_get_usage();
$memoryUsed = $memoryAfter - $memoryBefore;

if ($memoryUsed > 10 * 1024 * 1024) { // > 10MB
    Log::warning('High memory usage detected', ['memory_used' => $memoryUsed]);
}
```

## 📞 Support & Resources

### Documentation
- [Complete Module Guide](./ATTENDANCE_MODULE_GUIDE.md)
- [Quick Reference](./ATTENDANCE_QUICK_REFERENCE.md)
- [API Documentation](./api/attendance.yaml)

### Testing
- Unit Tests: `tests/Unit/Attendance/`
- Feature Tests: `tests/Feature/Attendance/`
- Test Coverage: Run with `--coverage-html`

### Development
- Laravel Documentation: https://laravel.com/docs
- PHPUnit Documentation: https://phpunit.de/documentation.html
- PHP Standards: https://www.php-fig.org/psr/

---

*Happy coding! 🚀*
