<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Modules\Attendance\Support\AttendanceType;
use PHPUnit\Framework\TestCase;

class AttendanceTypeTest extends TestCase
{
    public function test_normalize_aliases_to_flexible(): void
    {
        $this->assertSame(AttendanceType::FLEXIBLE, AttendanceType::normalize('flexible'));
        $this->assertSame(AttendanceType::FLEXIBLE, AttendanceType::normalize('flexable'));
        $this->assertSame(AttendanceType::FLEXIBLE, AttendanceType::normalize('FLEX'));
    }

    public function test_normalize_defaults_to_regular(): void
    {
        $this->assertSame(AttendanceType::REGULAR, AttendanceType::normalize(null));
        $this->assertSame(AttendanceType::REGULAR, AttendanceType::normalize(''));
        $this->assertSame(AttendanceType::REGULAR, AttendanceType::normalize('regular'));
        $this->assertSame(AttendanceType::REGULAR, AttendanceType::normalize('unknown'));
    }
}
