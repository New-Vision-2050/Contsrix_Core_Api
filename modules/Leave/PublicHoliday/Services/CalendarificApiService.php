<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CalendarificApiService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.calendarific.api_key');
        $this->baseUrl = config('services.calendarific.base_url');
    }

    /**
     * Fetch holidays for a specific country and year
     */
    public function getHolidays(string $countryCode, int $year): Collection
    {
        try {
            $response = Http::timeout(30)->get("{$this->baseUrl}/holidays", [
                'api_key' => $this->apiKey,
                'country' => $countryCode,
                'year' => $year,
                'language'=>"ar",
                'type' => 'national,local,religious,observance'
            ]);

            if (!$response->successful()) {
                Log::error("Calendarific API error for {$countryCode} {$year}", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return collect();
            }

            $data = $response->json();

            // Get holidays from response
            $holidays = $data['response']['holidays'] ?? [];

            if (empty($holidays)) {
                Log::warning("No holidays found for {$countryCode} {$year}");
                return collect();
            }

            Log::info("Found {count} holidays for {country} {year}", [
                'count' => count($holidays),
                'country' => $countryCode,
                'year' => $year
            ]);

            return collect($holidays)->map(function ($holiday) use ($countryCode, $year) {
                return $this->transformHoliday($holiday, $countryCode, $year);
            });

        } catch (\Exception $e) {
            Log::error("Exception fetching holidays for {$countryCode} {$year}", [
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Transform API holiday data to our format
     */
    private function transformHoliday(array $holiday, string $countryCode, int $year): array
    {
        try {
            // Extract date from ISO format (e.g., "2027-01-07" or "2027-03-20T22:24:39+02:00")
            $dateString = $holiday['date']['iso'] ?? null;

            if (!$dateString) {
                throw new \Exception('No ISO date found in holiday data');
            }

            // Parse date - handle both simple dates and datetime with timezone
            $date = Carbon::parse($dateString);

            return [
                'name' => $holiday['name'] ?? 'Unknown Holiday',
                'country_code' => $countryCode,
                'year' => $year,
                'date_start' => $date->format('Y-m-d'),
                'date_end' => $date->format('Y-m-d'),
                'holiday_type' => $this->determineHolidayType($holiday),
                'is_recurring' => true,
                'description' => $holiday['description'] ?? null,
                'external_api_id' => $holiday['urlid'] ?? null,
                'api_data' => json_encode([
                    'type' => $holiday['type'] ?? [],
                    'locations' => $holiday['locations'] ?? null,
                    'states' => $holiday['states'] ?? null,
                    'primary_type' => $holiday['primary_type'] ?? null,
                    'canonical_url' => $holiday['canonical_url'] ?? null,
                ]),
                'tags' => json_encode($this->extractTags($holiday)),
                'is_active' => true,
            ];

        } catch (\Exception $e) {
            Log::error("Error transforming holiday data", [
                'error' => $e->getMessage(),
                'holiday_name' => $holiday['name'] ?? 'Unknown',
                'country' => $countryCode,
                'year' => $year
            ]);

            // Return a fallback holiday
            return [
                'name' => $holiday['name'] ?? 'Unknown Holiday',
                'country_code' => $countryCode,
                'year' => $year,
                'date_start' => $year . '-01-01',
                'date_end' => $year . '-01-01',
                'holiday_type' => 'other',
                'is_recurring' => true,
                'description' => 'Error processing holiday data: ' . $e->getMessage(),
                'external_api_id' => null,
                'api_data' => json_encode($holiday),
                'tags' => json_encode(['error']),
                'is_active' => false,
            ];
        }
    }

    /**
     * Determine holiday type based on API data
     */
    private function determineHolidayType(array $holiday): string
    {
        $types = $holiday['type'] ?? [];

        if (in_array('National holiday', $types)) {
            return 'national';
        }

        if (in_array('Local holiday', $types)) {
            return 'local';
        }

        if (in_array('Religious', $types)) {
            return 'religious';
        }

        if (in_array('Observance', $types)) {
            return 'observance';
        }

        return 'other';
    }

    /**
     * Extract tags from holiday data
     */
    private function extractTags(array $holiday): array
    {
        $tags = [];

        // Add primary type as tag
        if (isset($holiday['primary_type'])) {
            $tags[] = strtolower($holiday['primary_type']);
        }

        // Add all types as tags
        if (isset($holiday['type']) && is_array($holiday['type'])) {
            foreach ($holiday['type'] as $type) {
                $tags[] = strtolower(str_replace(' ', '_', $type));
            }
        }

        // Add location-specific tags
        if (isset($holiday['locations']) && $holiday['locations'] !== 'All') {
            $tags[] = 'location_specific';
        }

        return array_unique($tags);
    }

    /**
     * Get supported countries from API
     */
    public function getSupportedCountries(): Collection
    {
        try {
            $response = Http::timeout(30)->get("{$this->baseUrl}/countries", [
                'api_key' => $this->apiKey
            ]);

            if (!$response->successful()) {
                Log::error("Failed to fetch supported countries", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return collect();
            }

            $data = $response->json();
            return collect($data['response']['countries'] ?? []);

        } catch (\Exception $e) {
            Log::error("Exception fetching supported countries", [
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Check API quota/usage
     */
    public function checkApiUsage(): array
    {
        try {
            // Make a simple request to check quota
            $response = Http::timeout(10)->get("{$this->baseUrl}/holidays", [
                'api_key' => $this->apiKey,
                'country' => 'US',
                'year' => date('Y'),
                'type' => 'national'
            ]);

            $headers = $response->headers();

            return [
                'quota_used' => $headers['X-Quota-Used'][0] ?? 'unknown',
                'quota_limit' => $headers['X-Quota-Limit'][0] ?? 'unknown',
                'quota_remaining' => $headers['X-Quota-Remaining'][0] ?? 'unknown',
                'status' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 'error'
            ];
        }
    }
}
