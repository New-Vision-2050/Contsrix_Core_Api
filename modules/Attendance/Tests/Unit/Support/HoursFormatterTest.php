<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Modules\Attendance\Support\HoursFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Locks down the contract for {@see HoursFormatter}, which is the single source of truth
 * for converting attendance work-hour / break / overtime / lateness values into the
 * "HH:MM" string used by all report and history APIs.
 *
 * The original mobile-history bug that motivated this class: a value of 9.93 decimal hours
 * (= 9h 56m) was being rendered as "09:93" by the FE because it was splitting the raw
 * decimal on the dot. These tests guarantee we never ship an unnormalised string again.
 */
final class HoursFormatterTest extends TestCase
{
    /** @dataProvider hoursProvider */
    public function test_from_hours(float $hours, string $expected): void
    {
        $this->assertSame($expected, HoursFormatter::fromHours($hours));
    }

    public static function hoursProvider(): array
    {
        return [
            'zero'                          => [0.0, '00:00'],
            'negative is clamped to zero'   => [-5.5, '00:00'],
            'one hour exact'                => [1.0, '01:00'],
            'half hour'                     => [0.5, '00:30'],
            'ten point five five (10h33m)'  => [10.55, '10:33'],
            'nine point five five (9h33m)'  => [9.55, '09:33'],
            'nine point ninety-three rounds to 9h56m (NOT 09:93 — that was the FE bug)'
                => [9.93, '09:56'],
            'zero point ninety-three rounds to 0h56m (NOT 00:93)' => [0.93, '00:56'],
            'just under a minute rounds to zero' => [0.008, '00:00'],
            'twenty-seven point five hours preserves day overflow' => [27.5, '27:30'],
            'rounds 0.999 hours up to 60min => 01:00' => [0.999, '01:00'],
        ];
    }

    /** @dataProvider minutesProvider */
    public function test_from_minutes(int $minutes, string $expected): void
    {
        $this->assertSame($expected, HoursFormatter::fromMinutes($minutes));
    }

    public static function minutesProvider(): array
    {
        return [
            'zero'                                            => [0, '00:00'],
            'negative is clamped to zero'                     => [-7, '00:00'],
            'three minutes'                                   => [3, '00:03'],
            'fifty-nine minutes'                              => [59, '00:59'],
            'sixty minutes carries to 01:00'                  => [60, '01:00'],
            'ninety-three minutes carries to 01:33 (NOT 00:93)' => [93, '01:33'],
            'six hundred thirty-three minutes (10h33m)'       => [633, '10:33'],
            'twenty-five hours nine minutes preserves overflow' => [1509, '25:09'],
        ];
    }

    public function test_from_decimal_string_handles_eloquent_decimal_cast_output(): void
    {
        // Eloquent's `decimal:2` cast returns the column as a STRING — verify the helper
        // accepts it without an explicit float cast at the call site.
        $this->assertSame('10:33', HoursFormatter::fromDecimalString('10.55'));
        $this->assertSame('09:56', HoursFormatter::fromDecimalString('9.93'));
        $this->assertSame('00:00', HoursFormatter::fromDecimalString('0.00'));
    }

    public function test_from_decimal_string_treats_null_and_empty_as_zero(): void
    {
        $this->assertSame('00:00', HoursFormatter::fromDecimalString(null));
        $this->assertSame('00:00', HoursFormatter::fromDecimalString(''));
    }

    public function test_from_decimal_string_accepts_float_and_int(): void
    {
        $this->assertSame('10:33', HoursFormatter::fromDecimalString(10.55));
        $this->assertSame('05:00', HoursFormatter::fromDecimalString(5));
    }

    /**
     * Locks the regression that motivated this class — the live screenshot showed
     * "09:93" / "00:93", which is impossible output from {@see HoursFormatter}.
     */
    public function test_never_emits_minutes_field_greater_than_or_equal_to_60(): void
    {
        for ($pennyHours = 0; $pennyHours <= 12000; $pennyHours += 17) {
            $hours = $pennyHours / 100; // 0.00, 0.17, 0.34, ..., 120.00
            $hm    = HoursFormatter::fromHours($hours);
            [, $minutes] = explode(':', $hm);
            $this->assertLessThan(60, (int) $minutes, "Minutes overflow for hours={$hours}: got {$hm}");
        }
    }
}
