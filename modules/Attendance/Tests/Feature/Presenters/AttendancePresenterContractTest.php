<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Presenters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Presenters\AttendancePresenter;
use Tests\TestCase;

/**
 * Contract test: locks the shape returned by AttendancePresenter::present().
 *
 * Uses a hydrated-but-unsaved Attendance model with relations pre-set via
 * setRelation() so no database is touched.  All assertions are key-shape only
 * — values are irrelevant; what matters is that the mobile API contract never
 * silently loses a field.
 */
final class AttendancePresenterContractTest extends TestCase
{
    private const EXPECTED_TOP_LEVEL_KEYS = [
        'id',
        'user_id',
        'company_id',
        'clock_in_time',
        'clock_out_time',
        'start_time',
        'end_time',
        'timezone',
        'total_work_hours',
        'total_break_hours',
        'overtime_hours',
        'is_late',
        'is_absent',
        'is_holiday',
        'is_early_departure',
        'late_minutes',
        'early_departure_minutes',
        'status',
        'approved_by',
        'approved_at',
        'clock_in_location',
        'clock_out_location',
        'notes',
        'ip_address',
        'created_at',
        'updated_at',
        'user',
        'company',
        'approved_by_user',
        'breaks',
        'work_date',
        'is_on_break',
        'is_clocked_in',
        'duration_formatted',
        'break_duration_formatted',
        'overtime_formatted',
        'day_status',
        'professional_data',
    ];

    private function makeAttendance(): Attendance
    {
        $attendance = new Attendance();
        $attendance->forceFill([
            'id'                       => 'aaaaaaaa-0000-0000-0000-000000000001',
            'user_id'                  => 'bbbbbbbb-0000-0000-0000-000000000002',
            'company_id'               => 'cccccccc-0000-0000-0000-000000000003',
            'clock_in_time'            => '2024-01-15 09:05:00',
            'clock_out_time'           => '2024-01-15 17:10:00',
            'start_time'               => '2024-01-15 09:00:00',
            'end_time'                 => '2024-01-15 17:00:00',
            'timezone'                 => 'Asia/Riyadh',
            'total_work_hours'         => 8.0,
            'total_break_hours'        => 0.5,
            'overtime_hours'           => 0.0,
            'is_late'                  => 0,
            'is_absent'                => 0,
            'is_holiday'               => 0,
            'is_early_departure'       => 0,
            'late_minutes'             => 0,
            'early_departure_minutes'  => 0,
            'status'                   => 'present',
            'approved_by'              => null,
            'approved_at'              => null,
            'clock_in_location'        => null,
            'clock_out_location'       => null,
            'notes'                    => null,
            'ip_address'               => null,
            'day_status'               => 'normal',
        ]);

        // Prevent Eloquent from querying the DB for these relations.
        $attendance->setRelation('user', null);
        $attendance->setRelation('company', null);
        $attendance->setRelation('approvedBy', null);
        $attendance->setRelation('breaks', new EloquentCollection());

        return $attendance;
    }

    public function test_present_returns_all_expected_keys(): void
    {
        $attendance = $this->makeAttendance();
        $presenter  = new AttendancePresenter($attendance);

        $payload = $presenter->present();

        foreach (self::EXPECTED_TOP_LEVEL_KEYS as $key) {
            $this->assertArrayHasKey($key, $payload, "Missing key '{$key}' from AttendancePresenter::present()");
        }
    }

    public function test_present_returns_no_unexpected_keys(): void
    {
        $attendance = $this->makeAttendance();
        $payload    = (new AttendancePresenter($attendance))->present();

        $extra = array_diff(array_keys($payload), self::EXPECTED_TOP_LEVEL_KEYS);

        $this->assertEmpty(
            $extra,
            'AttendancePresenter::present() added unexpected keys: ' . implode(', ', $extra)
        );
    }

    public function test_numeric_fields_are_cast_to_expected_types(): void
    {
        $attendance = $this->makeAttendance();
        $payload    = (new AttendancePresenter($attendance))->present();

        $this->assertIsFloat($payload['total_work_hours']);
        $this->assertIsFloat($payload['total_break_hours']);
        $this->assertIsFloat($payload['overtime_hours']);
        $this->assertIsInt($payload['is_late']);
        $this->assertIsInt($payload['is_absent']);
        $this->assertIsInt($payload['is_holiday']);
        $this->assertIsInt($payload['is_early_departure']);
        $this->assertIsInt($payload['is_clocked_in']);
    }

    public function test_breaks_is_always_array(): void
    {
        $attendance = $this->makeAttendance();
        $payload    = (new AttendancePresenter($attendance))->present();

        $this->assertIsArray($payload['breaks']);
    }

    public function test_user_is_null_when_relation_not_loaded(): void
    {
        $attendance = $this->makeAttendance();
        $payload    = (new AttendancePresenter($attendance))->present();

        $this->assertNull($payload['user']);
        $this->assertNull($payload['company']);
        $this->assertNull($payload['approved_by_user']);
        $this->assertNull($payload['professional_data']);
    }

    public function test_clock_in_time_format_is_datetime_string(): void
    {
        $attendance = $this->makeAttendance();
        $payload    = (new AttendancePresenter($attendance))->present();

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $payload['clock_in_time'],
            'clock_in_time must be Y-m-d H:i:s'
        );
    }

    public function test_work_date_format_is_date_string(): void
    {
        $attendance = $this->makeAttendance();
        $payload    = (new AttendancePresenter($attendance))->present();

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}$/',
            $payload['work_date'],
            'work_date must be Y-m-d'
        );
    }

    public function test_duration_formatted_fields_are_strings(): void
    {
        $attendance = $this->makeAttendance();
        $payload    = (new AttendancePresenter($attendance))->present();

        $this->assertIsString($payload['duration_formatted']);
        $this->assertIsString($payload['break_duration_formatted']);
        $this->assertIsString($payload['overtime_formatted']);
    }

    public function test_id_fields_are_strings_when_set(): void
    {
        $attendance = $this->makeAttendance();
        $payload    = (new AttendancePresenter($attendance))->present();

        $this->assertIsString($payload['id']);
        $this->assertIsString($payload['user_id']);
        $this->assertIsString($payload['company_id']);
    }
}
