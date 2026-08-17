<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Tests\Unit;

use Mockery;
use Modules\Project\ProjectManagement\Enums\ProjectReportCode;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectType\Models\SafetyRecord;
use Modules\Project\ProjectType\Services\SafetyViolationFormReportService;
use Modules\Project\ProjectType\Services\SafetyViolationReportService;
use Modules\Project\ProjectType\Services\SafetyViolationReportUrlResolver;
use PHPUnit\Framework\TestCase;

final class SafetyViolationReportUrlResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_jeddah_uses_form_report_service(): void
    {
        $resolver = $this->makeResolver(
            $makkah = Mockery::mock(SafetyViolationReportService::class),
            $jeddah = Mockery::mock(SafetyViolationFormReportService::class),
        );

        $jeddah->shouldReceive('storeAndGetPublicUrl')
            ->once()
            ->with('proj-1', 'rec-1')
            ->andReturn('https://cdn.example/form-report.pdf');
        $makkah->shouldNotReceive('storeAndGetPublicUrl');

        $url = $resolver->storeAndGetPublicUrl(
            $this->makeRecord(ProjectReportCode::Jeddah)
        );

        $this->assertSame('https://cdn.example/form-report.pdf', $url);
    }

    public function test_makkah_uses_existing_violation_report_service(): void
    {
        $resolver = $this->makeResolver(
            $makkah = Mockery::mock(SafetyViolationReportService::class),
            $jeddah = Mockery::mock(SafetyViolationFormReportService::class),
        );

        $makkah->shouldReceive('storeAndGetPublicUrl')
            ->once()
            ->with('proj-1', 'rec-1')
            ->andReturn('https://cdn.example/violation-report.pdf');
        $jeddah->shouldNotReceive('storeAndGetPublicUrl');

        $url = $resolver->storeAndGetPublicUrl(
            $this->makeRecord(ProjectReportCode::Makkah)
        );

        $this->assertSame('https://cdn.example/violation-report.pdf', $url);
    }

    public function test_missing_project_defaults_to_jeddah_form_report(): void
    {
        $resolver = $this->makeResolver(
            $makkah = Mockery::mock(SafetyViolationReportService::class),
            $jeddah = Mockery::mock(SafetyViolationFormReportService::class),
        );

        $jeddah->shouldReceive('storeAndGetPublicUrl')
            ->once()
            ->andReturn('https://cdn.example/form-report.pdf');
        $makkah->shouldNotReceive('storeAndGetPublicUrl');

        $record = new SafetyRecord();
        $record->id = 'rec-1';
        $record->project_id = 'proj-1';
        $record->setRelation('project', null);

        $this->assertSame(ProjectReportCode::Jeddah, $resolver->resolveCode(null));
        $this->assertSame(
            'https://cdn.example/form-report.pdf',
            $resolver->storeAndGetPublicUrl($record)
        );
    }

    private function makeResolver(
        SafetyViolationReportService $makkah,
        SafetyViolationFormReportService $jeddah,
    ): SafetyViolationReportUrlResolver {
        return new SafetyViolationReportUrlResolver($makkah, $jeddah);
    }

    private function makeRecord(ProjectReportCode $code): SafetyRecord
    {
        $project = new ProjectManagement();
        $project->id = 'proj-1';
        $project->code_report = $code;

        $record = new SafetyRecord();
        $record->id = 'rec-1';
        $record->project_id = 'proj-1';
        $record->setRelation('project', $project);

        return $record;
    }
}
