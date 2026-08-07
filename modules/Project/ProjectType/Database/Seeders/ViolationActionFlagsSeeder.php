<?php

namespace Modules\Project\ProjectType\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectType\Models\Violation;

/**
 * Updates only the three action flags on existing violations
 * from the electricity violations weights catalog (√ = true, - / empty = false).
 */
class ViolationActionFlagsSeeder extends Seeder
{
    public function run(): void
    {
        $flagsByCode = [
            '1-19-2-1' => [false, false, true],
            '2-19-2-1' => [false, true, true],
            '3-19-2-1' => [true, false, false],
            '4-19-2-1' => [true, false, false],
            '5-19-2-1' => [true, false, false],
            '6-19-2-1' => [true, false, false],
            '7-19-2-1' => [true, false, false],
            '8-19-2-1' => [false, true, true],
            '9-19-2-1' => [false, true, true],
            '10-19-2-1' => [false, true, false],
            '11-19-2-1' => [false, true, false],
            '12-19-2-1' => [false, true, false],
            '13-19-2-1' => [false, true, false],
            '14-19-2-1' => [false, true, false],
            '15-19-2-1' => [false, false, true],
            '16-19-2-1' => [false, false, true],
            '17-19-2-1' => [false, true, true],
            '18-19-2-1' => [false, true, false],
            '19-19-2-1' => [false, true, false],
            '20-19-2-1' => [false, false, true],
            '21-19-2-1' => [false, true, false],
            '22-19-2-1' => [false, false, true],
            '23-19-2-1' => [false, true, false],
            '24-19-2-1' => [false, true, false],
            // Excel rows without action marks (codes 25–34 in ViolationSeeder)
            '25-19-2-1' => [false, false, false],
            '26-19-2-1' => [false, false, false],
            '27-19-2-1' => [false, false, false],
            '28-19-2-1' => [false, false, false],
            '29-19-2-1' => [false, false, false],
            '30-19-2-1' => [false, false, false],
            '31-19-2-1' => [false, false, false],
            '32-19-2-1' => [false, false, false],
            '33-19-2-1' => [false, false, false],
            '34-19-2-1' => [false, false, false],
        ];

        foreach ($flagsByCode as $code => [$workCancellation, $workStop, $equipmentExclusion]) {
            Violation::query()
                ->where('code', $code)
                ->update([
                    'work_cancellation' => $workCancellation,
                    'work_stop' => $workStop,
                    'equipment_exclusion' => $equipmentExclusion,
                ]);
        }
    }
}
