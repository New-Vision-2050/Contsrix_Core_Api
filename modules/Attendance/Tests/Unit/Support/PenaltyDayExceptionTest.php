<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Carbon\Carbon;
use Modules\Attendance\Support\PenaltyDayException;
use PHPUnit\Framework\TestCase;

class PenaltyDayExceptionTest extends TestCase
{
    public function test_monthly_limit_defaults_to_three(): void
    {
        // config() is not booted in a pure unit test → the safe fallback applies.
        $this->assertSame(3, PenaltyDayException::monthlyLimit());
    }

    public function test_remaining_is_clamped_at_zero(): void
    {
        $this->assertSame(3, PenaltyDayException::remaining(3, 0));
        $this->assertSame(2, PenaltyDayException::remaining(3, 1));
        $this->assertSame(0, PenaltyDayException::remaining(3, 3));
        $this->assertSame(0, PenaltyDayException::remaining(3, 9));
    }

    public function test_only_penalized_rows_are_decidable(): void
    {
        $this->assertTrue(PenaltyDayException::isPenalized('auto_extension_penalty'));
        $this->assertFalse(PenaltyDayException::isPenalized('auto_extension_penalty_waived'));
        $this->assertFalse(PenaltyDayException::isPenalized('auto_max_ot'));
        $this->assertFalse(PenaltyDayException::isPenalized(null));
    }

    public function test_waived_method_is_recognised(): void
    {
        $this->assertTrue(PenaltyDayException::isWaived('auto_extension_penalty_waived'));
        $this->assertFalse(PenaltyDayException::isWaived('auto_extension_penalty'));
    }

    public function test_message_shows_only_on_first_clock_in_after_a_penalized_last_day(): void
    {
        $this->assertTrue(PenaltyDayException::shouldShowMessage(1, 'auto_extension_penalty', false, true));
    }

    public function test_message_hidden_before_clock_in_and_on_later_clock_ins(): void
    {
        $this->assertFalse(PenaltyDayException::shouldShowMessage(0, 'auto_extension_penalty', false, true));
        $this->assertFalse(PenaltyDayException::shouldShowMessage(2, 'auto_extension_penalty', false, true));
    }

    public function test_message_hidden_when_last_day_has_no_penalty(): void
    {
        // The employee clocked out by himself yesterday → no penalty → no message.
        $this->assertFalse(PenaltyDayException::shouldShowMessage(1, 'manual', false, true));
        $this->assertFalse(PenaltyDayException::shouldShowMessage(1, null, false, true));
        $this->assertFalse(PenaltyDayException::shouldShowMessage(1, 'auto_extension_penalty_waived', false, true));
    }

    public function test_message_hidden_once_decided_or_outside_window(): void
    {
        $this->assertFalse(PenaltyDayException::shouldShowMessage(1, 'auto_extension_penalty', true, true));
        $this->assertFalse(PenaltyDayException::shouldShowMessage(1, 'auto_extension_penalty', false, false));
    }

    public function test_decision_window_covers_the_current_month_only(): void
    {
        $now = Carbon::parse('2026-09-27 12:00:00');

        // Yesterday's penalty is decidable (the app shows the message the next day).
        $this->assertTrue(PenaltyDayException::inDecisionWindow('2026-09-26', $now));
        $this->assertTrue(PenaltyDayException::inDecisionWindow('2026-09-01', $now));
        $this->assertTrue(PenaltyDayException::inDecisionWindow('2026-09-27', $now));

        // Last month's penalty is not.
        $this->assertFalse(PenaltyDayException::inDecisionWindow('2026-08-31', $now));
        $this->assertFalse(PenaltyDayException::inDecisionWindow(null, $now));
        $this->assertFalse(PenaltyDayException::inDecisionWindow('', $now));
    }
}
