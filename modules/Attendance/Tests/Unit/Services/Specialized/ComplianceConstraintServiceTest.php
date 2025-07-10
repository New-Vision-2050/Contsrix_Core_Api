<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services\Specialized;

use PHPUnit\Framework\TestCase;
use Modules\Attendance\Services\ComplianceConstraintService;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;

class ComplianceConstraintServiceTest extends TestCase
{
    private ComplianceConstraintService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComplianceConstraintService();
    }

    /**
     * Test labor law validation with compliant work hours
     */
    public function test_labor_law_validation_compliant_hours(): void
    {
        // Create mock user with location for region-specific rules
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 1],
            ['name', 'John Doe'],
            ['region_code', 'US-CA'] // California region code
        ]);
        
        // Create mock attendance with acceptable work hours
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['work_hours', 8], // 8 hours (below 12 hour California limit)
            ['break_duration', 60] // 60 minutes break (meets California requirement)
        ]);
        
        // Create constraint checking labor law compliance
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_COMPLIANCE],
            ['subtype', AttendanceConstraint::COMPLIANCE_LABOR_LAW],
            ['config', [
                'region_rules' => [
                    'US-CA' => [
                        'max_daily_hours' => 12,
                        'required_break_minutes' => 30
                    ],
                    'default' => [
                        'max_daily_hours' => 10,
                        'required_break_minutes' => 30
                    ]
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateComplianceConstraint($attendance, $constraint);
        
        // No violation should be detected when meeting labor law requirements
        $this->assertFalse($result);
    }

    /**
     * Test labor law validation with non-compliant work hours
     */
    public function test_labor_law_validation_excessive_hours(): void
    {
        // Create mock user with location for region-specific rules
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 2],
            ['name', 'Jane Smith'],
            ['region_code', 'US-CA'] // California region code
        ]);
        
        // Create mock attendance exceeding work hour limits
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['work_hours', 14], // 14 hours (exceeds 12 hour California limit)
            ['break_duration', 30] // 30 minutes break (meets minimum requirement)
        ]);
        
        // Create constraint checking labor law compliance
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_COMPLIANCE],
            ['subtype', AttendanceConstraint::COMPLIANCE_LABOR_LAW],
            ['config', [
                'region_rules' => [
                    'US-CA' => [
                        'max_daily_hours' => 12,
                        'required_break_minutes' => 30
                    ],
                    'default' => [
                        'max_daily_hours' => 10,
                        'required_break_minutes' => 30
                    ]
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateComplianceConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::COMPLIANCE_LABOR_LAW, $result['constraint_type']);
        $this->assertStringContainsString('labor law', strtolower($result['message']));
        $this->assertEquals(14, $result['details']['work_hours']);
        $this->assertEquals(12, $result['details']['max_allowed_hours']);
    }

    /**
     * Test union agreement validation with compliant clock-in time
     */
    public function test_union_agreement_validation_compliant(): void
    {
        // Create mock user with union affiliation
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 3],
            ['name', 'Bob Johnson'],
            ['union_code', 'UAW123'] // United Auto Workers union code
        ]);
        
        // Create mock attendance within allowed work times
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['clock_in', '08:00:00'],
            ['clock_out', '16:30:00'],
            ['overtime_hours', 0]
        ]);
        
        // Create constraint checking union agreement compliance
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_COMPLIANCE],
            ['subtype', AttendanceConstraint::COMPLIANCE_UNION_AGREEMENT],
            ['config', [
                'union_rules' => [
                    'UAW123' => [
                        'earliest_clock_in' => '07:30:00',
                        'latest_clock_out' => '17:00:00',
                        'max_overtime_hours' => 2
                    ],
                    'default' => [
                        'earliest_clock_in' => '08:00:00',
                        'latest_clock_out' => '18:00:00',
                        'max_overtime_hours' => 3
                    ]
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateComplianceConstraint($attendance, $constraint);
        
        // No violation should be detected when meeting union agreement
        $this->assertFalse($result);
    }

    /**
     * Test union agreement validation with non-compliant overtime hours
     */
    public function test_union_agreement_validation_overtime_violation(): void
    {
        // Create mock user with union affiliation
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 4],
            ['name', 'Sarah Wilson'],
            ['union_code', 'UAW123'] // United Auto Workers union code
        ]);
        
        // Create mock attendance with excessive overtime
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['clock_in', '08:00:00'],
            ['clock_out', '18:30:00'],
            ['overtime_hours', 3] // Exceeds the 2-hour overtime limit
        ]);
        
        // Create constraint checking union agreement compliance
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_COMPLIANCE],
            ['subtype', AttendanceConstraint::COMPLIANCE_UNION_AGREEMENT],
            ['config', [
                'union_rules' => [
                    'UAW123' => [
                        'earliest_clock_in' => '07:30:00',
                        'latest_clock_out' => '17:00:00',
                        'max_overtime_hours' => 2
                    ],
                    'default' => [
                        'earliest_clock_in' => '08:00:00',
                        'latest_clock_out' => '18:00:00',
                        'max_overtime_hours' => 3
                    ]
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateComplianceConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::COMPLIANCE_UNION_AGREEMENT, $result['constraint_type']);
        $this->assertStringContainsString('overtime', strtolower($result['message']));
        $this->assertEquals(3, $result['details']['overtime_hours']);
        $this->assertEquals(2, $result['details']['max_allowed_overtime']);
    }

    /**
     * Test industry rules validation with compliant certifications
     */
    public function test_industry_rules_validation_compliant(): void
    {
        // Create mock user with certifications
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 5],
            ['name', 'Michael Brown'],
            ['industry_code', 'HEALTHCARE'],
            ['certifications', ['CPR', 'FirstAid', 'HIPAA']]
        ]);
        
        // Create mock attendance with standard hours
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['consecutive_work_days', 3], // Not exceeding maximum
            ['rest_minutes', 45] // Meet minimum rest requirements
        ]);
        
        // Create constraint checking industry rules compliance
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_COMPLIANCE],
            ['subtype', AttendanceConstraint::COMPLIANCE_INDUSTRY_RULES],
            ['config', [
                'industry_rules' => [
                    'HEALTHCARE' => [
                        'required_certifications' => ['CPR', 'HIPAA'],
                        'max_consecutive_days' => 5,
                        'min_rest_minutes' => 30
                    ],
                    'default' => [
                        'required_certifications' => [],
                        'max_consecutive_days' => 7,
                        'min_rest_minutes' => 15
                    ]
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateComplianceConstraint($attendance, $constraint);
        
        // No violation should be detected when meeting industry rules
        $this->assertFalse($result);
    }

    /**
     * Test industry rules validation with missing required certifications
     */
    public function test_industry_rules_validation_missing_certifications(): void
    {
        // Create mock user with missing certifications
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 6],
            ['name', 'Emily Davis'],
            ['industry_code', 'HEALTHCARE'],
            ['certifications', ['FirstAid']] // Missing CPR and HIPAA
        ]);
        
        // Create mock attendance
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['consecutive_work_days', 3],
            ['rest_minutes', 45]
        ]);
        
        // Create constraint checking industry rules compliance
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_COMPLIANCE],
            ['subtype', AttendanceConstraint::COMPLIANCE_INDUSTRY_RULES],
            ['config', [
                'industry_rules' => [
                    'HEALTHCARE' => [
                        'required_certifications' => ['CPR', 'HIPAA'],
                        'max_consecutive_days' => 5,
                        'min_rest_minutes' => 30
                    ],
                    'default' => [
                        'required_certifications' => [],
                        'max_consecutive_days' => 7,
                        'min_rest_minutes' => 15
                    ]
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateComplianceConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::COMPLIANCE_INDUSTRY_RULES, $result['constraint_type']);
        $this->assertStringContainsString('certification', strtolower($result['message']));
        $this->assertContains('CPR', $result['details']['missing_certifications']);
        $this->assertContains('HIPAA', $result['details']['missing_certifications']);
    }

    /**
     * Test government reporting validation with compliant data
     */
    public function test_government_reporting_validation_compliant(): void
    {
        // Create mock user with tax IDs
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 7],
            ['name', 'Alex Thompson'],
            ['tax_id', 'SSN123456789'],
            ['country_code', 'US']
        ]);
        
        // Create mock attendance with required fields
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['metadata', [
                'wage_code' => 'W2',
                'department_code' => 'SALES',
                'pay_category' => 'HOURLY'
            ]]
        ]);
        
        // Create constraint checking government reporting compliance
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_COMPLIANCE],
            ['subtype', AttendanceConstraint::COMPLIANCE_GOVERNMENT_REPORTING],
            ['config', [
                'country_requirements' => [
                    'US' => [
                        'required_fields' => ['wage_code', 'department_code', 'pay_category'],
                        'tax_id_format' => 'SSN\d{9}'
                    ],
                    'default' => [
                        'required_fields' => ['department_code'],
                        'tax_id_format' => '.+'
                    ]
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateComplianceConstraint($attendance, $constraint);
        
        // No violation should be detected when meeting government reporting requirements
        $this->assertFalse($result);
    }

    /**
     * Test government reporting validation with missing required fields
     */
    public function test_government_reporting_validation_missing_fields(): void
    {
        // Create mock user with valid tax ID
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 8],
            ['name', 'Jamie Wilson'],
            ['tax_id', 'SSN987654321'],
            ['country_code', 'US']
        ]);
        
        // Create mock attendance with missing required fields
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['metadata', [
                // Missing wage_code and pay_category
                'department_code' => 'ENGINEERING'
            ]]
        ]);
        
        // Create constraint checking government reporting compliance
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_COMPLIANCE],
            ['subtype', AttendanceConstraint::COMPLIANCE_GOVERNMENT_REPORTING],
            ['config', [
                'country_requirements' => [
                    'US' => [
                        'required_fields' => ['wage_code', 'department_code', 'pay_category'],
                        'tax_id_format' => 'SSN\d{9}'
                    ],
                    'default' => [
                        'required_fields' => ['department_code'],
                        'tax_id_format' => '.+'
                    ]
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateComplianceConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::COMPLIANCE_GOVERNMENT_REPORTING, $result['constraint_type']);
        $this->assertStringContainsString('required field', strtolower($result['message']));
        $this->assertContains('wage_code', $result['details']['missing_fields']);
        $this->assertContains('pay_category', $result['details']['missing_fields']);
    }

    /**
     * Test documentation validation with compliant data
     */
    public function test_documentation_validation_compliant(): void
    {
        // Create mock user
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 9],
            ['name', 'Taylor Lee']
        ]);
        
        // Create mock attendance with required documentation
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['documentation', [
                'manager_approval' => true,
                'approval_notes' => 'Approved by manager.',
                'reason_code' => 'STANDARD',
                'attachments' => ['timesheet.pdf']
            ]]
        ]);
        
        // Create constraint checking documentation compliance
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_COMPLIANCE],
            ['subtype', AttendanceConstraint::COMPLIANCE_DOCUMENTATION],
            ['config', [
                'required_documentation' => [
                    'manager_approval' => true,
                    'approval_notes' => true,
                    'reason_code' => true,
                    'attachments' => true
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateComplianceConstraint($attendance, $constraint);
        
        // No violation should be detected when meeting documentation requirements
        $this->assertFalse($result);
    }

    /**
     * Test documentation validation with missing required documentation
     */
    public function test_documentation_validation_missing_documents(): void
    {
        // Create mock user
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 10],
            ['name', 'Morgan Rivers']
        ]);
        
        // Create mock attendance with missing documentation
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['documentation', [
                'manager_approval' => false, // Missing manager approval
                'reason_code' => 'STANDARD',
                // Missing approval notes and attachments
            ]]
        ]);
        
        // Create constraint checking documentation compliance
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_COMPLIANCE],
            ['subtype', AttendanceConstraint::COMPLIANCE_DOCUMENTATION],
            ['config', [
                'required_documentation' => [
                    'manager_approval' => true,
                    'approval_notes' => true,
                    'reason_code' => true,
                    'attachments' => true
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateComplianceConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::COMPLIANCE_DOCUMENTATION, $result['constraint_type']);
        $this->assertStringContainsString('documentation', strtolower($result['message']));
        $this->assertFalse($result['details']['has_manager_approval']);
        $this->assertContains('approval_notes', $result['details']['missing_documentation']);
        $this->assertContains('attachments', $result['details']['missing_documentation']);
    }
}
