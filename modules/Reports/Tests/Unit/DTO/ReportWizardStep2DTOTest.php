<?php

declare(strict_types=1);

namespace Modules\Reports\Tests\Unit\DTO;

use Modules\Reports\DTO\ReportWizardStep2DTO;
use PHPUnit\Framework\TestCase;

class ReportWizardStep2DTOTest extends TestCase
{
    public function test_missing_constraint_ids_default_to_empty(): void
    {
        $dto = ReportWizardStep2DTO::fromArray([]);

        $this->assertSame([], $dto->attendanceConstraintIds);
        $this->assertSame([], $dto->toArray()['attendance_constraint_ids']);
    }

    public function test_snake_case_ids_are_kept_and_deduplicated(): void
    {
        $id = '9f2c1a10-4b3e-4d21-9c8a-1a2b3c4d5e6f';

        $dto = ReportWizardStep2DTO::fromArray([
            'attendance_constraint_ids' => [$id, $id, ''],
        ]);

        $this->assertSame([$id], $dto->attendanceConstraintIds);
    }

    public function test_camel_case_alias_is_accepted(): void
    {
        $id = '9f2c1a10-4b3e-4d21-9c8a-1a2b3c4d5e6f';

        $dto = ReportWizardStep2DTO::fromArray([
            'attendanceConstraintIds' => [$id],
        ]);

        $this->assertSame([$id], $dto->toArray()['attendance_constraint_ids']);
    }
}
