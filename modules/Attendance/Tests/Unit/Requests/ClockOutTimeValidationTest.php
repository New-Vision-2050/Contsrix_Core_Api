<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Requests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Clock-out used to merge branch-local now() then validate before_or_equal:now
 * (app TZ, usually UTC). Riyadh 16:50 vs UTC 13:50 looked like the future.
 */
class ClockOutTimeValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_omitted_clock_out_time_is_accepted(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-14 13:50:00', 'UTC'));
        config(['app.timezone' => 'UTC']);

        $nowInBranch = Carbon::now('Asia/Riyadh')->toDateTimeString();

        $validator = Validator::make(
            ['location' => ['latitude' => 21.62, 'longitude' => 39.12]],
            [
                'clock_out_time' => ['sometimes', 'nullable', 'date', 'before_or_equal:'.$nowInBranch],
            ]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_branch_local_now_is_not_future_against_branch_now(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-14 13:50:00', 'UTC'));
        config(['app.timezone' => 'UTC']);

        $branchNow = Carbon::now('Asia/Riyadh')->toDateTimeString();

        $legacy = Validator::make(
            ['clock_out_time' => $branchNow],
            ['clock_out_time' => ['date', 'before_or_equal:now']]
        );
        $this->assertTrue($legacy->fails(), 'UTC before_or_equal:now must reject a Riyadh wall-clock now');

        $fixed = Validator::make(
            ['clock_out_time' => $branchNow],
            ['clock_out_time' => ['date', 'before_or_equal:'.$branchNow]]
        );
        $this->assertFalse($fixed->fails());
    }
}
