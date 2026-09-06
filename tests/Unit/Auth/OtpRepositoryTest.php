<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use Ichtrojan\Otp\Models\Otp;
use Mockery;
use Modules\Auth\Repositories\OtpRepository;
use PHPUnit\Framework\TestCase;

class OtpRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_returns_the_newest_otp_for_the_employee_identifiers(): void
    {
        $otp = Mockery::mock(Otp::class);

        $query = Mockery::mock();
        $query->shouldReceive('whereIn')
            ->once()
            ->with('identifier', ['employee@example.test', '+201000000000'])
            ->andReturnSelf();
        $query->shouldReceive('latest')
            ->once()
            ->with('created_at')
            ->andReturnSelf();
        $query->shouldReceive('first')
            ->once()
            ->andReturn($otp);

        $model = Mockery::mock(Otp::class);
        $model->shouldReceive('newQuery')
            ->once()
            ->andReturn($query);

        $repository = new OtpRepository($model);

        $lastOtp = $repository->getLatestForIdentifiers([
            'employee@example.test',
            null,
            '',
            '+201000000000',
        ]);

        $this->assertSame($otp, $lastOtp);
    }

    public function test_it_does_not_query_when_the_employee_has_no_identifiers(): void
    {
        $model = Mockery::mock(Otp::class);
        $model->shouldNotReceive('newQuery');

        $repository = new OtpRepository($model);

        $this->assertNull($repository->getLatestForIdentifiers([null, '']));
    }
}
