<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Unit\Enums;

use Modules\Project\ProjectManagement\Enums\ProjectReportCode;
use PHPUnit\Framework\TestCase;

final class ProjectReportCodeTest extends TestCase
{
    public function test_db_values_are_stable(): void
    {
        $this->assertSame('jeddah', ProjectReportCode::Jeddah->value);
        $this->assertSame('makkah', ProjectReportCode::Makkah->value);
    }

    public function test_default_is_jeddah(): void
    {
        $this->assertSame(ProjectReportCode::Jeddah, ProjectReportCode::default());
    }

    public function test_values_lists_only_allowed_codes(): void
    {
        $this->assertSame(['jeddah', 'makkah'], ProjectReportCode::values());
    }
}
