<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Country\Models\Country;
use Modules\Leave\PublicHoliday\Models\PublicHoliday;
use Modules\Leave\PublicHoliday\Services\CalendarificApiService;
use Carbon\Carbon;

class CalendarificHolidaySeeder extends Seeder
{
    private CalendarificApiService $apiService;

    // Target countries with their ISO2 codes
    private array $targetCountries = [
        'EG' => 'Egypt',
        'SA' => 'Saudi Arabia',
        'JO' => 'Jordan',
        'SD' => 'Sudan',
        'AE' => 'United Arab Emirates',
        'KW' => 'Kuwait'
    ];

    // Years to seed (15 years starting from 2025)
    private int $startYear = 2025;
    private int $endYear = 2040;

    public function __construct()
    {
        $this->apiService = new CalendarificApiService();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎉 Starting Calendarific Holiday Seeder');
        $this->command->info("📅 Seeding holidays for years {$this->startYear} to {$this->endYear}");
        $this->command->info("🌍 Target countries: " . implode(', ', array_values($this->targetCountries)));

        // Check API usage first
        $this->checkApiUsage();

        // Get country mappings
        $countryMappings = $this->getCountryMappings();

        if (empty($countryMappings)) {
            $this->command->error('❌ No target countries found in database');
            return;
        }

        $totalHolidays = 0;
        $totalRequests = 0;
        $errors = [];

        // Process each country and year
        foreach ($this->targetCountries as $countryCode => $countryName) {
            if (!isset($countryMappings[$countryCode])) {
                $this->command->warn("⚠️  Country {$countryName} ({$countryCode}) not found in database");
                continue;
            }

            $country = $countryMappings[$countryCode];
            $this->command->info("\n🏳️  Processing {$countryName} ({$countryCode})...");

            $countryHolidays = 0;

            for ($year = $this->startYear; $year <= $this->endYear; $year++) {
                try {
                    $this->command->info("  📆 Fetching holidays for {$year}...");

                    // Add delay to respect API rate limits
                    if ($totalRequests > 0) {
                        sleep(1); // 1 second delay between requests
                    }

                    $holidays = $this->apiService->getHolidays($countryCode, $year);
                    $totalRequests++;

                    if ($holidays->isEmpty()) {
                        $this->command->warn("    ⚠️  No holidays found for {$countryName} {$year}");
                        continue;
                    }

                    $yearHolidays = $this->processHolidays($holidays, $country, $year);
                    $countryHolidays += $yearHolidays;
                    $totalHolidays += $yearHolidays;

                    $this->command->info("    ✅ Added {$yearHolidays} holidays for {$year}");

                } catch (\Exception $e) {
                    $error = "Error processing {$countryName} {$year}: " . $e->getMessage();
                    $errors[] = $error;
                    $this->command->error("    ❌ {$error}");
                    Log::error($error, ['exception' => $e]);
                }
            }

            $this->command->info("  🎯 Total holidays for {$countryName}: {$countryHolidays}");
        }

        // Final summary
        $this->command->info("\n" . str_repeat('=', 60));
        $this->command->info("🎉 SEEDING COMPLETED!");
        $this->command->info("📊 Summary:");
        $this->command->info("   • Total API requests: {$totalRequests}");
        $this->command->info("   • Total holidays added: {$totalHolidays}");
        $this->command->info("   • Countries processed: " . count(array_intersect_key($countryMappings, $this->targetCountries)));
        $this->command->info("   • Years covered: {$this->startYear} - {$this->endYear}");

        if (!empty($errors)) {
            $this->command->warn("⚠️  Errors encountered: " . count($errors));
            foreach ($errors as $error) {
                $this->command->error("   • {$error}");
            }
        }

        $this->command->info(str_repeat('=', 60));
    }

    /**
     * Check API usage and quota
     */
    private function checkApiUsage(): void
    {
        $this->command->info('🔍 Checking API usage...');

        $usage = $this->apiService->checkApiUsage();

        if (isset($usage['error'])) {
            $this->command->warn("⚠️  Could not check API usage: {$usage['error']}");
            return;
        }

        $this->command->info("📈 API Usage:");
        $this->command->info("   • Used: {$usage['quota_used']}");
        $this->command->info("   • Limit: {$usage['quota_limit']}");
        $this->command->info("   • Remaining: {$usage['quota_remaining']}");

        // Calculate estimated requests needed
        $estimatedRequests = count($this->targetCountries) * ($this->endYear - $this->startYear + 1);
        $this->command->info("   • Estimated requests needed: {$estimatedRequests}");

        if (is_numeric($usage['quota_remaining']) && $usage['quota_remaining'] < $estimatedRequests) {
            $this->command->error("❌ Insufficient API quota! Need {$estimatedRequests}, have {$usage['quota_remaining']}");
            if (!$this->command->confirm('Continue anyway?')) {
                exit(1);
            }
        }
    }

    /**
     * Get country mappings from database
     */
    private function getCountryMappings(): array
    {
        $countries = Country::whereIn('iso2', array_keys($this->targetCountries))
                           ->get()
                           ->keyBy('iso2');

        $mappings = [];
        foreach ($countries as $country) {
            $mappings[$country->iso2] = $country;
        }

        return $mappings;
    }

    /**
     * Process and save holidays for a country and year
     */
    private function processHolidays($holidays, Country $country, int $year): int
    {
        $saved = 0;
        $batch = [];

        foreach ($holidays as $holidayData) {
            // Check if holiday already exists
            $existing = PublicHoliday::where('country_id', $country->id)
                                   ->where('year', $year)
                                   ->where('name', $holidayData['name'])
                                   ->where('date_start', $holidayData['date_start'])
                                   ->first();

            if ($existing) {
                // Update existing holiday with new data
                $existing->update([
                    'country_code' => $holidayData['country_code'],
                    'holiday_type' => $holidayData['holiday_type'],
                    'description' => $holidayData['description'],
                    'external_api_id' => $holidayData['external_api_id'],
                    'api_data' => $holidayData['api_data'],
                    'tags' => $holidayData['tags'],
                    'is_active' => $holidayData['is_active'],
                ]);
                continue;
            }

            // Prepare data for batch insert
            $batch[] = array_merge($holidayData, [
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'country_id' => $country->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $saved++;

            // Insert in batches of 50 to avoid memory issues
            if (count($batch) >= 50) {
                DB::table('public_holidays')->insert($batch);
                $batch = [];
            }
        }

        // Insert remaining holidays
        if (!empty($batch)) {
            DB::table('public_holidays')->insert($batch);
        }

        return $saved;
    }
}
