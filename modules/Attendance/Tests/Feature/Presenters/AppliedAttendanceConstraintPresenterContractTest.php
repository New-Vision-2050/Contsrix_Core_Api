<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Presenters;

use Modules\Attendance\Models\AppliedAttendanceConstraint;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Presenters\AppliedAttendanceConstraintPresenter;
use Tests\TestCase;

/**
 * Contract test: locks the 11-key shape of AppliedAttendanceConstraintPresenter::present()
 * and verifies the null path when no constraint is attached.
 * No database access — models are hydrated via forceFill() / setRelation().
 */
final class AppliedAttendanceConstraintPresenterContractTest extends TestCase
{
    private const EXPECTED_KEYS = [
        'id',
        'constraint_name',
        'constraint_type',
        'constraint_code',
        'branch_locations',
        'notes',
        'is_active',
        'priority',
        'start_date',
        'end_date',
        'config',
    ];

    private function makeSnapshot(): array
    {
        return [
            'id'                => '00000000-0000-0000-0000-000000000099',
            'constraint_name'   => 'Standard Shift',
            'constraint_type'   => 'time_based',
            'branch_locations'  => [],
            'notes'             => null,
            'is_active'         => 1,
            'priority'          => 1,
            'start_date'        => '2024-01-01',
            'end_date'          => null,
            'constraint_config' => [
                'time_rules' => [
                    'lateness_rules' => [
                        'lateness_period' => 5,
                        'lateness_unit'   => 'minute',
                    ],
                ],
            ],
        ];
    }

    private function makeAttendanceWithConstraint(?array $snapshot): Attendance
    {
        $attendance = new Attendance();

        if ($snapshot !== null) {
            $constraint = new AppliedAttendanceConstraint();
            $constraint->forceFill(['constraint_snapshot' => $snapshot]);
            $attendance->setRelation('appliedAttendanceConstraint', $constraint);
        } else {
            $attendance->setRelation('appliedAttendanceConstraint', null);
        }

        return $attendance;
    }

    public function test_present_returns_null_when_no_constraint(): void
    {
        $presenter = new AppliedAttendanceConstraintPresenter(
            $this->makeAttendanceWithConstraint(null)
        );

        $this->assertNull($presenter->present());
    }

    public function test_present_returns_all_expected_keys(): void
    {
        $attendance = $this->makeAttendanceWithConstraint($this->makeSnapshot());
        $payload    = (new AppliedAttendanceConstraintPresenter($attendance))->present();

        foreach (self::EXPECTED_KEYS as $key) {
            $this->assertArrayHasKey(
                $key,
                $payload,
                "Missing key '{$key}' from AppliedAttendanceConstraintPresenter::present()"
            );
        }
    }

    public function test_present_returns_no_unexpected_keys(): void
    {
        $attendance = $this->makeAttendanceWithConstraint($this->makeSnapshot());
        $payload    = (new AppliedAttendanceConstraintPresenter($attendance))->present();
        $extra      = array_diff(array_keys($payload), self::EXPECTED_KEYS);

        $this->assertEmpty(
            $extra,
            'AppliedAttendanceConstraintPresenter::present() added unexpected keys: ' . implode(', ', $extra)
        );
    }

    public function test_id_is_string(): void
    {
        $payload = (new AppliedAttendanceConstraintPresenter(
            $this->makeAttendanceWithConstraint($this->makeSnapshot())
        ))->present();

        $this->assertIsString($payload['id']);
    }

    public function test_is_active_is_int(): void
    {
        $payload = (new AppliedAttendanceConstraintPresenter(
            $this->makeAttendanceWithConstraint($this->makeSnapshot())
        ))->present();

        $this->assertIsInt($payload['is_active']);
    }

    public function test_priority_is_int(): void
    {
        $payload = (new AppliedAttendanceConstraintPresenter(
            $this->makeAttendanceWithConstraint($this->makeSnapshot())
        ))->present();

        $this->assertIsInt($payload['priority']);
    }

    public function test_constraint_type_is_translated_string(): void
    {
        $payload = (new AppliedAttendanceConstraintPresenter(
            $this->makeAttendanceWithConstraint($this->makeSnapshot())
        ))->present();

        $this->assertIsString($payload['constraint_type']);
    }

    public function test_constraint_code_is_raw_type_from_snapshot(): void
    {
        $snapshot = $this->makeSnapshot();
        $payload  = (new AppliedAttendanceConstraintPresenter(
            $this->makeAttendanceWithConstraint($snapshot)
        ))->present();

        $this->assertSame($snapshot['constraint_type'], $payload['constraint_code']);
    }

    public function test_config_is_array(): void
    {
        $payload = (new AppliedAttendanceConstraintPresenter(
            $this->makeAttendanceWithConstraint($this->makeSnapshot())
        ))->present();

        $this->assertIsArray($payload['config']);
    }

    public function test_weekly_schedule_days_are_ordered_sat_to_fri(): void
    {
        $snapshot = $this->makeSnapshot();
        $snapshot['constraint_config']['time_rules']['weekly_schedule'] = [
            'monday'    => ['enabled' => true,  'periods' => []],
            'friday'    => ['enabled' => false, 'periods' => []],
            'saturday'  => ['enabled' => true,  'periods' => []],
            'sunday'    => ['enabled' => false, 'periods' => []],
            'tuesday'   => ['enabled' => true,  'periods' => []],
            'wednesday' => ['enabled' => false, 'periods' => []],
            'thursday'  => ['enabled' => true,  'periods' => []],
        ];

        $payload = (new AppliedAttendanceConstraintPresenter(
            $this->makeAttendanceWithConstraint($snapshot)
        ))->present();

        $days = array_keys($payload['config']['time_rules']['weekly_schedule']);

        $this->assertSame(
            ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            $days
        );
    }
}
