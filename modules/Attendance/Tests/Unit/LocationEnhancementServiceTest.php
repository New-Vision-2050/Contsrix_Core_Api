<?php

namespace Modules\Attendance\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attendance\Services\LocationEnhancementService;
use Modules\Attendance\Services\LocationTrackingService;
use Modules\Attendance\Models\Attendance;
use Modules\Company\CompanyCore\Services\CompanyProfileService;
use Mockery;

class LocationEnhancementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $locationEnhancementService;
    protected $mockCompanyProfileService;
    protected $mockLocationTrackingService;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->mockCompanyProfileService = Mockery::mock(CompanyProfileService::class);
        $this->mockLocationTrackingService = Mockery::mock(LocationTrackingService::class);
        
        // Create service with mocks
        $this->locationEnhancementService = new LocationEnhancementService(
            $this->mockCompanyProfileService,
            $this->mockLocationTrackingService
        );
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_enhances_attendance_location_data()
    {
        // Create a test attendance record with location data
        $attendance = new Attendance([
            'clock_in_location' => [
                'latitude' => 37.7749,
                'longitude' => -122.4194,
                'address' => '123 Test Street'
            ]
        ]);
        
        // Set up mock response from CompanyProfileService
        $enhancedLocationData = [
            'address' => '123 Main Street',
            'city' => 'San Francisco',
            'state' => 'California',
            'country' => 'United States'
        ];
        
        $this->mockCompanyProfileService
            ->shouldReceive('geoCoding')
            ->once()
            ->andReturn($enhancedLocationData);
            
        // LocationTrackingService should be called to update tracking points
        $this->mockLocationTrackingService
            ->shouldReceive('addTrackingPoints')
            ->once()
            ->andReturn(true);
            
        // Mock the save method on the attendance model
        $attendance = Mockery::mock(Attendance::class)->makePartial();
        $attendance->shouldReceive('save')->once()->andReturn(true);
        
        $attendance->clock_in_location = [
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'address' => '123 Test Street'
        ];
        
        // Call the service method
        $result = $this->locationEnhancementService->enhanceLocationData($attendance);
        
        // Verify the result contains enhanced data
        $this->assertArrayHasKey('clock_in', $result);
        $this->assertEquals('123 Main Street', $result['clock_in']['address']);
        $this->assertEquals('San Francisco', $result['clock_in']['city']);
        $this->assertEquals('California', $result['clock_in']['state']);
        $this->assertEquals('United States', $result['clock_in']['country']);
        $this->assertTrue($result['clock_in']['enhanced']);
    }

    /** @test */
    public function it_calculates_distance_between_locations_correctly()
    {
        // Test data - coordinates for San Francisco and Los Angeles
        $location1 = [
            'latitude' => 37.7749,
            'longitude' => -122.4194
        ];
        
        $location2 = [
            'latitude' => 34.0522,
            'longitude' => -118.2437
        ];
        
        // Calculate distance
        $distance = $this->locationEnhancementService->calculateDistanceToOffice($location1, $location2);
        
        // Distance between SF and LA should be approximately 559 km or 559000 meters
        // We allow for some calculation variance by checking it's within a reasonable range
        $this->assertGreaterThan(550000, $distance);
        $this->assertLessThan(570000, $distance);
    }

    /** @test */
    public function it_handles_invalid_location_data_gracefully()
    {
        // Create an attendance with missing location data
        $attendance = new Attendance();
        $attendance->clock_in_location = null;
        
        // Expect no calls to the mocked services
        $this->mockCompanyProfileService->shouldReceive('geoCoding')->never();
        $this->mockLocationTrackingService->shouldReceive('addTrackingPoints')->never();
        
        // Call the service method - this should not throw an exception
        $result = $this->locationEnhancementService->enhanceLocationData($attendance);
        
        // Verify empty result
        $this->assertEmpty($result);
    }
}
