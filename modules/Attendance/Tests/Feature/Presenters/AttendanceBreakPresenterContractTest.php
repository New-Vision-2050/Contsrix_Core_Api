<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Presenters;

use Modules\Attendance\Models\AttendanceBreak;
use Modules\Attendance\Presenters\AttendanceBreakPresenter;
use Tests\TestCase;

/**
 * Contract test: locks the 12-key shape of AttendanceBreakPresenter::present().
 * No database access — models are hydrated via forceFill().
 */
final class AttendanceBreakPresenterContractTest extends TestCase
{
    private const EXPECTED_KEYS = [
        'id',
        'attendance_id',
        'company_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'duration_formatted',
        'notes',
        'created_at',
        'updated_at',
        'is_active',
        'is_completed',
    ];

    private function makeCompletedBreak(): AttendanceBreak
    {
        $break = new AttendanceBreak();
        $break->forceFill([
            'id'               => 'dddddddd-0000-0000-0000-000000000004',
            'attendance_id'    => 'aaaaaaaa-0000-0000-0000-000000000001',
            'company_id'       => 'cccccccc-0000-0000-0000-000000000003',
            'start_time'       => '2024-01-15 10:00:00',
            'end_time'         => '2024-01-15 10:30:00',
            'duration_minutes' => 30,
            'notes'            => null,
            'created_at'       => '2024-01-15 10:00:00',
            'updated_at'       => '2024-01-15 10:30:00',
        ]);

        return $break;
    }

    private function makeActiveBreak(): AttendanceBreak
    {
        $break = new AttendanceBreak();
        $break->forceFill([
            'id'               => 'dddddddd-0000-0000-0000-000000000005',
            'attendance_id'    => 'aaaaaaaa-0000-0000-0000-000000000001',
            'company_id'       => 'cccccccc-0000-0000-0000-000000000003',
            'start_time'       => '2024-01-15 10:00:00',
            'end_time'         => null,
            'duration_minutes' => null,
            'notes'            => 'coffee break',
            'created_at'       => '2024-01-15 10:00:00',
            'updated_at'       => '2024-01-15 10:00:00',
        ]);

        return $break;
    }

    public function test_present_returns_all_expected_keys(): void
    {
        $payload = (new AttendanceBreakPresenter($this->makeCompletedBreak()))->present();

        foreach (self::EXPECTED_KEYS as $key) {
            $this->assertArrayHasKey($key, $payload, "Missing key '{$key}' from AttendanceBreakPresenter::present()");
        }
    }

    public function test_present_returns_no_unexpected_keys(): void
    {
        $payload = (new AttendanceBreakPresenter($this->makeCompletedBreak()))->present();
        $extra   = array_diff(array_keys($payload), self::EXPECTED_KEYS);

        $this->assertEmpty(
            $extra,
            'AttendanceBreakPresenter::present() added unexpected keys: ' . implode(', ', $extra)
        );
    }

    public function test_id_fields_are_strings_when_set(): void
    {
        $payload = (new AttendanceBreakPresenter($this->makeCompletedBreak()))->present();

        $this->assertIsString($payload['id']);
        $this->assertIsString($payload['attendance_id']);
        $this->assertIsString($payload['company_id']);
    }

    public function test_datetime_fields_have_correct_format(): void
    {
        $payload = (new AttendanceBreakPresenter($this->makeCompletedBreak()))->present();

        foreach (['start_time', 'end_time', 'created_at', 'updated_at'] as $key) {
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
                $payload[$key],
                "'{$key}' must be Y-m-d H:i:s"
            );
        }
    }

    public function test_end_time_is_null_for_active_break(): void
    {
        $payload = (new AttendanceBreakPresenter($this->makeActiveBreak()))->present();

        $this->assertNull($payload['end_time']);
    }

    public function test_duration_minutes_is_integer_when_set(): void
    {
        $payload = (new AttendanceBreakPresenter($this->makeCompletedBreak()))->present();

        $this->assertIsInt($payload['duration_minutes']);
    }

    public function test_duration_minutes_is_null_for_active_break(): void
    {
        $payload = (new AttendanceBreakPresenter($this->makeActiveBreak()))->present();

        $this->assertNull($payload['duration_minutes']);
    }

    public function test_duration_formatted_is_string(): void
    {
        $payload = (new AttendanceBreakPresenter($this->makeCompletedBreak()))->present();

        $this->assertIsString($payload['duration_formatted']);
    }

    public function test_completed_break_has_correct_flags(): void
    {
        $payload = (new AttendanceBreakPresenter($this->makeCompletedBreak()))->present();

        $this->assertTrue($payload['is_completed']);
        $this->assertFalse($payload['is_active']);
    }

    public function test_active_break_has_correct_flags(): void
    {
        $payload = (new AttendanceBreakPresenter($this->makeActiveBreak()))->present();

        $this->assertTrue($payload['is_active']);
        $this->assertFalse($payload['is_completed']);
    }
}
