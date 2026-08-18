<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Project\ProjectManagement\Enums\ProjectReportCode;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Tests\TestCase;

final class ProjectReportCodeColumnTest extends TestCase
{
    use DatabaseTransactions;

    public function test_projects_code_report_is_not_null_enum_defaulting_to_jeddah(): void
    {
        if (! Schema::hasTable('projects')) {
            $this->markTestSkipped('projects table is missing.');
        }

        if (! Schema::hasColumn('projects', 'code_report')) {
            $this->markTestSkipped('code_report column is missing. Run migrations.');
        }

        $column = collect(DB::select('SHOW COLUMNS FROM projects WHERE Field = ?', ['code_report']))->first();

        $this->assertNotNull($column);
        $this->assertMatchesRegularExpression(
            "/^enum\\s*\\(\\s*'jeddah'\\s*,\\s*'makkah'\\s*\\)$/i",
            (string) $column->Type
        );
        $this->assertSame('NO', strtoupper((string) $column->Null));
        $this->assertSame(ProjectReportCode::Jeddah->value, (string) $column->Default);
    }

    public function test_existing_projects_are_not_null(): void
    {
        if (! Schema::hasColumn('projects', 'code_report')) {
            $this->markTestSkipped('code_report column is missing. Run migrations.');
        }

        $this->assertSame(
            0,
            (int) DB::table('projects')->whereNull('code_report')->count()
        );
    }

    public function test_new_project_without_code_report_defaults_to_jeddah(): void
    {
        if (! Schema::hasColumn('projects', 'code_report')) {
            $this->markTestSkipped('code_report column is missing. Run migrations.');
        }

        $existing = ProjectManagement::withoutGlobalScopes()->first();
        if (! $existing) {
            $this->markTestSkipped('No existing project available to clone required keys.');
        }

        $source = (array) DB::table('projects')->where('id', $existing->id)->first();
        unset($source['code_report']);

        $id = (string) Str::uuid();
        $source['id'] = $id;
        $source['name'] = 'Code Report Default Test';
        $source['serial_number'] = 'CRT-'.substr(str_replace('-', '', $id), 0, 12);
        $source['created_at'] = now();
        $source['updated_at'] = now();

        DB::table('projects')->insert($source);

        $this->assertSame(
            ProjectReportCode::Jeddah->value,
            DB::table('projects')->where('id', $id)->value('code_report')
        );

        $project = ProjectManagement::withoutGlobalScopes()->find($id);
        $this->assertSame(ProjectReportCode::Jeddah, $project?->code_report);
    }
}
