<?php

namespace Modules\Project\ProjectType\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Project\ProjectType\Database\Seeders\ViolationActionFlagsSeeder;
use Modules\Project\ProjectType\Database\Seeders\ViolationSeeder;
use Modules\Project\ProjectType\Models\Violation;
use Modules\Project\ProjectType\Presenters\ViolationPresenter;
use Tests\TestCase;

class ViolationActionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->violationsTableReady()) {
            $this->markTestSkipped('violations table / action columns are not available.');
        }
    }

    public function test_presenter_includes_computed_actions(): void
    {
        $violation = Violation::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'ACT-'.Str::upper(Str::random(4)),
            'description' => 'Presenter actions test',
            'category' => 'B',
            'default_weight' => 2,
            'work_cancellation' => false,
            'work_stop' => true,
            'equipment_exclusion' => true,
        ]);

        $data = (new ViolationPresenter($violation))->getData(true);

        $this->assertSame(
            ['إيقاف العمل', 'استبعاد المعدة أو الموظف'],
            $data['actions']
        );
    }

    public function test_seeder_persists_action_flags_from_catalog_table(): void
    {
        (new ViolationSeeder())->run();
        (new ViolationActionFlagsSeeder())->run();

        $cases = [
            '1-19-2-1' => [false, false, true, ['استبعاد المعدة أو الموظف']],
            '2-19-2-1' => [false, true, true, ['إيقاف العمل', 'استبعاد المعدة أو الموظف']],
            '3-19-2-1' => [true, false, false, ['إلغاء العمل']],
            '8-19-2-1' => [false, true, true, ['إيقاف العمل', 'استبعاد المعدة أو الموظف']],
            '10-19-2-1' => [false, true, false, ['إيقاف العمل']],
            '15-19-2-1' => [false, false, true, ['استبعاد المعدة أو الموظف']],
            '25-19-2-1' => [false, false, false, []],
            '34-19-2-1' => [false, false, false, []],
        ];

        foreach ($cases as $code => [$cancellation, $stop, $exclusion, $actions]) {
            $violation = Violation::query()->where('code', $code)->first();

            $this->assertNotNull($violation, "Missing seeded violation {$code}");
            $this->assertSame($cancellation, (bool) $violation->work_cancellation, $code.' work_cancellation');
            $this->assertSame($stop, (bool) $violation->work_stop, $code.' work_stop');
            $this->assertSame($exclusion, (bool) $violation->equipment_exclusion, $code.' equipment_exclusion');
            $this->assertSame($actions, $violation->actions(), $code.' actions');
        }
    }

    private function violationsTableReady(): bool
    {
        try {
            return Schema::hasTable('violations')
                && Schema::hasColumn('violations', 'work_cancellation')
                && Schema::hasColumn('violations', 'work_stop')
                && Schema::hasColumn('violations', 'equipment_exclusion');
        } catch (\Throwable) {
            return false;
        }
    }
}
