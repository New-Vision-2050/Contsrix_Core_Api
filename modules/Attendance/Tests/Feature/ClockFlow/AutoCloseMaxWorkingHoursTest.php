<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\ClockFlow;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AutoCloseAttendanceService;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Verifies AutoCloseAttendanceService::resolveAutoCloseMoment() net-based decisions:
 *  - Regular phase closes once NET worked reaches max_working_hours (deterministic moment).
 *  - Shift stays open before the target is reached.
 *  - A re-clock-in overtime session closes at the shift window end (overtime confined to window).
 *  - Legacy constraints (no max_working_hours) still close at end_time + max_over_time.
 *
 * @group requires-db
 */
final class AutoCloseMaxWorkingHoursTest extends TestCase
{
    use DatabaseTransactions;

    private AutoCloseAttendanceService $service;
    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(AutoCloseAttendanceService::class);
        $this->company = Company::factory()->create(['status' => 'active']);
        $this->user    = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
    }

    private function makeShift(array $overrides = []): Attendance
    {
        $now = CarbonImmutable::now('Asia/Riyadh');

        return Attendance::create(array_merge([
            'user_id'           => $this->user->id,
            'company_id'        => $this->company->id,
            'status'            => Attendance::STATUS_ACTIVE,
            'clock_in_time'     => $now->subHours(7)->format('Y-m-d H:i:s'),
            'clock_out_time'    => null,
            'start_time'        => $now->subHours(7)->format('Y-m-d H:i:s'),
            'end_time'          => $now->addHour()->format('Y-m-d H:i:s'), // 8h window
            'timezone'          => 'Asia/Riyadh',
            'max_working_hours' => 6,
            'max_over_time'     => 2,
        ], $overrides));
    }

    public function test_regular_close_when_net_reaches_max_working_hours(): void
    {
        $shift = $this->makeShift(); // clocked in 7h ago, W=6 → net (7h) >= 6h
        $now   = CarbonImmutable::now('Asia/Riyadh');

        $decision = $this->service->resolveAutoCloseMoment($shift, $now);

        $this->assertNotNull($decision, 'Shift should be due for regular auto-close');
        [$moment, $reason] = $decision;
        $this->assertSame('auto_max_hours', $reason);

        // Deterministic moment = clock_in + max_working_hours (no breaks) = 6h after clock-in.
        $clockIn = CarbonImmutable::parse($shift->clock_in_time, 'Asia/Riyadh');
        $this->assertSame(
            $clockIn->addHours(6)->format('Y-m-d H:i'),
            $moment->format('Y-m-d H:i'),
        );
    }

    public function test_shift_stays_open_before_reaching_target(): void
    {
        $now   = CarbonImmutable::now('Asia/Riyadh');
        $shift = $this->makeShift([
            'clock_in_time' => $now->subHours(3)->format('Y-m-d H:i:s'), // only 3h < W=6
            'start_time'    => $now->subHours(3)->format('Y-m-d H:i:s'),
            'end_time'      => $now->addHours(5)->format('Y-m-d H:i:s'),
        ]);

        $this->assertNull($this->service->resolveAutoCloseMoment($shift, $now));
    }

    public function test_overtime_session_closes_at_window_end(): void
    {
        $now = CarbonImmutable::now('Asia/Riyadh');

        $start = $now->subHours(7);
        $end   = $now->addHour(); // window ends 1h from now

        // Prior completed row that already consumed the full 6h regular quota in this period.
        Attendance::create([
            'user_id'           => $this->user->id,
            'company_id'        => $this->company->id,
            'status'            => Attendance::STATUS_COMPLETED,
            'clock_in_time'     => $start->format('Y-m-d H:i:s'),
            'clock_out_time'    => $start->addHours(6)->format('Y-m-d H:i:s'),
            'start_time'        => $start->format('Y-m-d H:i:s'),
            'end_time'          => $end->format('Y-m-d H:i:s'),
            'timezone'          => 'Asia/Riyadh',
            'total_work_hours'  => 6,
            'max_working_hours' => 6,
            'max_over_time'     => 2,
        ]);

        // Active overtime re-clock-in that started 10 min ago; max_over_time (2h) not yet reached,
        // so the binding limit is the shift window end.
        $otShift = Attendance::create([
            'user_id'           => $this->user->id,
            'company_id'        => $this->company->id,
            'status'            => Attendance::STATUS_ACTIVE,
            'clock_in_time'     => $now->subMinutes(10)->format('Y-m-d H:i:s'),
            'clock_out_time'    => null,
            'start_time'        => $start->format('Y-m-d H:i:s'),
            'end_time'          => $end->format('Y-m-d H:i:s'),
            'timezone'          => 'Asia/Riyadh',
            'max_working_hours' => 6,
            'max_over_time'     => 2,
        ]);

        $decision = $this->service->resolveAutoCloseMoment($otShift, $now);

        // Not yet due (window end is 1h away, OT cap 2h away).
        $this->assertNull($decision);

        // At/after the window end it becomes due, capped to the window end.
        $decisionAtEnd = $this->service->resolveAutoCloseMoment($otShift, $end->addMinute());
        $this->assertNotNull($decisionAtEnd);
        [$moment, $reason] = $decisionAtEnd;
        $this->assertSame('auto_max_ot', $reason);
        $this->assertSame($end->format('Y-m-d H:i'), $moment->format('Y-m-d H:i'));
    }

    public function test_legacy_close_when_no_max_working_hours(): void
    {
        $now = CarbonImmutable::now('Asia/Riyadh');

        $shift = $this->makeShift([
            'clock_in_time'     => $now->subHours(9)->format('Y-m-d H:i:s'),
            'start_time'        => $now->subHours(9)->format('Y-m-d H:i:s'),
            'end_time'          => $now->subHour()->format('Y-m-d H:i:s'), // window ended 1h ago
            'max_working_hours' => null,
            'max_over_time'     => 0,
        ]);

        $decision = $this->service->resolveAutoCloseMoment($shift, $now);

        $this->assertNotNull($decision);
        [$moment, $reason] = $decision;
        $this->assertSame('auto_max_ot', $reason);
        // Legacy: closes at end_time (max_over_time = 0).
        $this->assertSame(
            CarbonImmutable::parse($shift->end_time, 'Asia/Riyadh')->format('Y-m-d H:i'),
            $moment->format('Y-m-d H:i'),
        );
    }
}
