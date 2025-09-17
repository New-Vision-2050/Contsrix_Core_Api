<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Tests;

use Tests\TestCase;
use Modules\Leave\PublicHoliday\Services\CalendarificApiService;
use Illuminate\Support\Facades\Http;

class CalendarificApiServiceTest extends TestCase
{
    private CalendarificApiService $apiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiService = new CalendarificApiService();
    }

    /** @test */
    public function it_can_fetch_holidays_for_egypt_2025()
    {
        // Skip if no API key configured
        if (!config('services.calendarific.api_key')) {
            $this->markTestSkipped('Calendarific API key not configured');
        }

        $holidays = $this->apiService->getHolidays('EG', 2025);

        $this->assertNotEmpty($holidays);
        $this->assertTrue($holidays->count() > 0);

        // Check that we have October 6th holiday (Egypt's national day)
        $october6Holiday = $holidays->first(function ($holiday) {
            return str_contains(strtolower($holiday['name']), 'october') ||
                   str_contains(strtolower($holiday['name']), '6th') ||
                   $holiday['date_start'] === '2025-10-06';
        });

        $this->assertNotNull($october6Holiday, 'October 6th holiday should be present for Egypt');
    }

    /** @test */
    public function it_can_check_api_usage()
    {
        // Skip if no API key configured
        if (!config('services.calendarific.api_key')) {
            $this->markTestSkipped('Calendarific API key not configured');
        }

        $usage = $this->apiService->checkApiUsage();

        $this->assertIsArray($usage);
        $this->assertArrayHasKey('status', $usage);
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        // Mock HTTP response for error case
        Http::fake([
            '*' => Http::response(['error' => 'Invalid API key'], 401)
        ]);

        $holidays = $this->apiService->getHolidays('EG', 2025);

        $this->assertTrue($holidays->isEmpty());
    }

    /** @test */
    public function it_transforms_holiday_data_correctly()
    {
        // Mock successful API response
        Http::fake([
            '*' => Http::response([
                'response' => [
                    'holidays' => [
                        [
                            'name' => 'Test Holiday',
                            'description' => 'Test Description',
                            'date' => ['iso' => '2025-10-06'],
                            'type' => ['National holiday'],
                            'primary_type' => 'National holiday',
                            'uuid' => 'test-uuid-123'
                        ]
                    ]
                ]
            ])
        ]);

        $holidays = $this->apiService->getHolidays('EG', 2025);

        $this->assertCount(1, $holidays);

        $holiday = $holidays->first();
        $this->assertEquals('Test Holiday', $holiday['name']);
        $this->assertEquals('EG', $holiday['country_code']);
        $this->assertEquals(2025, $holiday['year']);
        $this->assertEquals('2025-10-06', $holiday['date_start']);
        $this->assertEquals('national', $holiday['holiday_type']);
        $this->assertTrue($holiday['is_active']);
    }
}
