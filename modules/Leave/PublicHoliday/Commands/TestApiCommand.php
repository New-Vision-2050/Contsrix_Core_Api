<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Commands;

use Illuminate\Console\Command;
use Modules\Leave\PublicHoliday\Services\CalendarificApiService;
use Illuminate\Support\Facades\Http;

class TestApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'holidays:test-api
                            {country=EG : Country code to test}
                            {year=2027 : Year to test}';

    /**
     * The console command description.
     */
    protected $description = 'Test Calendarific API connection and response';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $country = $this->argument('country');
        $year = (int) $this->argument('year');

        $this->info("🧪 Testing Calendarific API");
        $this->info("Country: {$country}");
        $this->info("Year: {$year}");
        $this->info("API Key: " . (config('services.calendarific.api_key') ? '✅ Configured' : '❌ Missing'));

        // Test direct HTTP call first
        $this->info("\n📡 Making direct API call...");

        try {
            $url = config('services.calendarific.base_url') . '/holidays';
            $params = [
                'api_key' => config('services.calendarific.api_key'),
                'country' => $country,
                'year' => $year,
                'type' => 'national,local,religious,observance'
            ];

            $this->info("URL: {$url}");
            $this->info("Params: " . json_encode(array_merge($params, ['api_key' => '***hidden***'])));

            $response = Http::timeout(30)->get($url, $params);

            $this->info("Status: " . $response->status());
            $this->info("Response size: " . strlen($response->body()) . " bytes");

            if (!$response->successful()) {
                $this->error("❌ API call failed!");
                $this->error("Response: " . $response->body());
                return self::FAILURE;
            }

            $data = $response->json();

            $this->info("\n📊 Response structure:");
            $this->info("Top-level keys: " . json_encode(array_keys($data ?? [])));

            if (isset($data['response'])) {
                $this->info("Response keys: " . json_encode(array_keys($data['response'])));

                if (isset($data['response']['holidays'])) {
                    $holidays = $data['response']['holidays'];
                    $this->info("Holidays found: " . count($holidays));

                    if (count($holidays) > 0) {
                        $this->info("\n🎉 Sample holiday:");
                        $firstHoliday = $holidays[0];
                        $this->table(
                            ['Field', 'Value'],
                            [
                                ['Name', $firstHoliday['name'] ?? 'N/A'],
                                ['Date', isset($firstHoliday['date']['iso']) ? $firstHoliday['date']['iso'] : 'N/A'],
                                ['Type', json_encode($firstHoliday['type'] ?? [])],
                                ['Primary Type', $firstHoliday['primary_type'] ?? 'N/A'],
                                ['Description', $firstHoliday['description'] ?? 'N/A'],
                            ]
                        );

                        // Show all holidays
                        $this->info("\n📅 All holidays for {$country} {$year}:");
                        $holidayTable = [];
                        foreach ($holidays as $holiday) {
                            $holidayTable[] = [
                                $holiday['name'] ?? 'N/A',
                                isset($holiday['date']['iso']) ? $holiday['date']['iso'] : 'N/A',
                                $holiday['primary_type'] ?? 'N/A',
                            ];
                        }
                        $this->table(['Name', 'Date', 'Type'], $holidayTable);
                    }
                } else {
                    $this->warn("⚠️  No 'holidays' key in response");
                }
            } else {
                $this->warn("⚠️  No 'response' key in data");
                $this->info("Full response: " . json_encode($data, JSON_PRETTY_PRINT));
            }

            // Test with service
            $this->info("\n🔧 Testing with CalendarificApiService...");
            $apiService = new CalendarificApiService();
            $holidays = $apiService->getHolidays($country, $year);

            $this->info("Service returned: " . $holidays->count() . " holidays");

            if ($holidays->count() > 0) {
                $this->info("✅ Service working correctly!");
                $firstHoliday = $holidays->first();
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Name', $firstHoliday['name']],
                        ['Date Start', $firstHoliday['date_start']],
                        ['Holiday Type', $firstHoliday['holiday_type']],
                        ['Country Code', $firstHoliday['country_code']],
                    ]
                );
            } else {
                $this->error("❌ Service returned no holidays - check logs for details");
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
