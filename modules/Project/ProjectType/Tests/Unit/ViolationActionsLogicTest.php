<?php

namespace Modules\Project\ProjectType\Tests\Unit;

use Modules\Project\ProjectType\Models\Violation;
use PHPUnit\Framework\TestCase;

class ViolationActionsLogicTest extends TestCase
{
    public function test_actions_returns_only_enabled_arabic_labels_in_order(): void
    {
        $violation = new Violation([
            'work_cancellation' => true,
            'work_stop' => true,
            'equipment_exclusion' => true,
        ]);

        $this->assertSame(
            ['إلغاء العمل', 'إيقاف العمل', 'استبعاد المعدة أو الموظف'],
            $violation->actions()
        );
    }

    public function test_actions_returns_empty_array_when_all_flags_false(): void
    {
        $violation = new Violation([
            'work_cancellation' => false,
            'work_stop' => false,
            'equipment_exclusion' => false,
        ]);

        $this->assertSame([], $violation->actions());
    }

    public function test_actions_partial_flags_match_catalog_examples(): void
    {
        $stopAndExclude = new Violation([
            'work_cancellation' => false,
            'work_stop' => true,
            'equipment_exclusion' => true,
        ]);

        $this->assertSame(
            ['إيقاف العمل', 'استبعاد المعدة أو الموظف'],
            $stopAndExclude->actions()
        );

        $cancellationOnly = new Violation([
            'work_cancellation' => true,
            'work_stop' => false,
            'equipment_exclusion' => false,
        ]);

        $this->assertSame(['إلغاء العمل'], $cancellationOnly->actions());
    }
}
