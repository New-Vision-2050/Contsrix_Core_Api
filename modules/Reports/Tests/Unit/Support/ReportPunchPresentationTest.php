<?php

declare(strict_types=1);

namespace Modules\Reports\Tests\Unit\Support;

use Modules\Reports\Support\ReportPunchPresentation;
use PHPUnit\Framework\TestCase;

class ReportPunchPresentationTest extends TestCase
{
    public function test_open_shift_has_no_clock_out_cause(): void
    {
        $this->assertSame('', ReportPunchPresentation::clockOutCauseCode(null, null));
        $this->assertSame('', ReportPunchPresentation::clockOutCauseCode('manual', ''));
    }

    public function test_empty_method_with_clock_out_is_employee(): void
    {
        $this->assertSame(
            'manual',
            ReportPunchPresentation::clockOutCauseCode(null, '2026-09-15 17:00:00')
        );
        $this->assertSame('الموظف', ReportPunchPresentation::clockOutCauseLabel('manual', 'ar'));
        $this->assertSame('Employee', ReportPunchPresentation::clockOutCauseLabel('manual', 'en'));
    }

    public function test_auto_close_methods_keep_their_code(): void
    {
        $this->assertSame(
            'auto_max_ot',
            ReportPunchPresentation::clockOutCauseCode('auto_max_ot', '2026-09-15 17:00:00')
        );
        $this->assertSame(
            'Auto — out of zone',
            ReportPunchPresentation::clockOutCauseLabel('auto_out_zone', 'en')
        );
        $this->assertSame(
            'Auto — extension penalty',
            ReportPunchPresentation::clockOutCauseLabel('auto_extension_penalty', 'en')
        );
        $this->assertSame(
            'تلقائي — جزاء معفى',
            ReportPunchPresentation::clockOutCauseLabel('auto_extension_penalty_waived', 'ar')
        );
    }

    public function test_penalty_minutes_only_for_auto_extension_penalty(): void
    {
        // 16:06:31 expected vs 13:51:31 stored → 135 min deducted.
        $this->assertSame(
            135,
            ReportPunchPresentation::penaltyMinutes(
                'auto_extension_penalty',
                '2026-09-24 16:06:31',
                '2026-09-24 13:51:31',
            )
        );

        // Any other close reason carries no penalty.
        $this->assertSame(
            0,
            ReportPunchPresentation::penaltyMinutes('auto_max_ot', '2026-09-24 16:06:31', '2026-09-24 13:51:31')
        );
        $this->assertSame(
            0,
            ReportPunchPresentation::penaltyMinutes('manual', '2026-09-24 16:06:31', '2026-09-24 13:51:31')
        );

        // Missing/garbled times or a stored time after the expected end → no penalty.
        $this->assertSame(0, ReportPunchPresentation::penaltyMinutes('auto_extension_penalty', null, '2026-09-24 13:51:31'));
        $this->assertSame(0, ReportPunchPresentation::penaltyMinutes('auto_extension_penalty', '2026-09-24 16:06:31', null));
        $this->assertSame(
            0,
            ReportPunchPresentation::penaltyMinutes('auto_extension_penalty', '2026-09-24 13:51:31', '2026-09-24 16:06:31')
        );
    }

    public function test_penalty_label_is_human_hours(): void
    {
        $this->assertSame('', ReportPunchPresentation::penaltyLabel(0, 'en'));
        $this->assertSame('2 hours', ReportPunchPresentation::penaltyLabel(120, 'en'));
        $this->assertSame('1 hour', ReportPunchPresentation::penaltyLabel(60, 'en'));
        $this->assertSame('2.25 hours', ReportPunchPresentation::penaltyLabel(135, 'en'));
        $this->assertSame('1.5 hours', ReportPunchPresentation::penaltyLabel(90, 'en'));

        $this->assertSame('1 ساعة', ReportPunchPresentation::penaltyLabel(60, 'ar'));
        $this->assertSame('2 ساعتان', ReportPunchPresentation::penaltyLabel(120, 'ar'));
        $this->assertSame('2.25 ساعات', ReportPunchPresentation::penaltyLabel(135, 'ar'));
    }

    public function test_location_prefers_address_then_coordinates(): void
    {
        $this->assertSame(
            'King Fahd Rd',
            ReportPunchPresentation::locationLabel([
                'address' => 'King Fahd Rd',
                'latitude' => 24.7136,
                'longitude' => 46.6753,
            ])
        );

        $this->assertSame(
            '24.71360, 46.67530',
            ReportPunchPresentation::locationLabel('{"latitude":24.7136,"longitude":46.6753}')
        );

        $this->assertSame(
            '24.71360, 46.67530',
            ReportPunchPresentation::locationLabel(['lat' => 24.7136, 'lng' => 46.6753])
        );

        $this->assertSame('', ReportPunchPresentation::locationLabel(null));
        $this->assertSame('', ReportPunchPresentation::locationLabel(''));
    }
}
