<?php

declare(strict_types=1);

namespace Modules\Shared\PCloud\Tests\Unit\Jobs;

use Modules\Shared\PCloud\Jobs\SyncMediaToPCloudJob;
use PHPUnit\Framework\TestCase;

final class SyncMediaToPCloudJobTest extends TestCase
{
    public function test_job_properties_are_serializable(): void
    {
        $job = new SyncMediaToPCloudJob('abc-123', 'company-xyz');

        $this->assertSame('abc-123', $job->mediaId);
        $this->assertSame('company-xyz', $job->companyId);
        $this->assertSame(3, $job->tries);
        $this->assertSame(30, $job->backoff);
    }
}
