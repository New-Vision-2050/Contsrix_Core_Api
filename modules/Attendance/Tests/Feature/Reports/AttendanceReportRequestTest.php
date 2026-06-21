<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Reports;

use Illuminate\Support\Facades\Validator;
use Modules\Attendance\DTO\AttendanceReportFilterDTO;
use Modules\Attendance\Requests\AttendanceReportRequest;

class AttendanceReportRequestTest extends BaseAttendanceReportTestCase
{
    public function test_validates_required_employee_id(): void
    {
        $this->actingAs($this->actor, 'api');

        $validator = Validator::make([], (new AttendanceReportRequest)->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('employee_id', $validator->errors()->toArray());
    }

    public function test_validates_date_ordering(): void
    {
        $this->actingAs($this->actor, 'api');

        $validator = Validator::make([
            'employee_id' => (string) $this->employee->id,
            'from_date' => '2025-05-31',
            'to_date' => '2025-05-01',
        ], (new AttendanceReportRequest)->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_accepts_valid_filter_payload(): void
    {
        $this->actingAs($this->actor, 'api');

        $validator = Validator::make([
            'employee_id' => (string) $this->employee->id,
            'from_date' => '2025-05-01',
            'to_date' => '2025-05-31',
            'year' => 2025,
            'month' => 5,
            'page' => 1,
            'per_page' => 12,
        ], (new AttendanceReportRequest)->rules());

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray()));
    }

    public function test_month_requires_year(): void
    {
        $this->actingAs($this->actor, 'api');

        $validator = Validator::make([
            'employee_id' => (string) $this->employee->id,
            'month' => 5,
        ], (new AttendanceReportRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('year', $validator->errors()->toArray());
    }

    public function test_unsupported_filters_are_not_part_of_report_rules_or_dto(): void
    {
        $this->actingAs($this->actor, 'api');

        $rules = (new AttendanceReportRequest)->rules();
        $dto = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
        );

        foreach (['department_id', 'branch_id', 'status'] as $unsupportedFilter) {
            $this->assertArrayNotHasKey($unsupportedFilter, $rules);
            $this->assertArrayNotHasKey($unsupportedFilter, $dto->toArray());
        }

        $this->assertArrayHasKey('page', $rules);
        $this->assertArrayHasKey('per_page', $rules);
        $this->assertSame(1, $dto->toArray()['page']);
        $this->assertSame(12, $dto->toArray()['per_page']);
    }
}
