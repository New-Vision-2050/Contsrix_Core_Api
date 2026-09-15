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
