<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Commands;

use Illuminate\Console\Command;
use Modules\Leave\PublicHoliday\Database\Seeders\CalendarificHolidaySeeder;

class SeedHolidaysCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'holidays:seed
                            {--countries=* : Specific country codes to seed (e.g., EG,SA,JO)}
                            {--years=* : Specific years to seed (e.g., 2025,2026)}
                            {--start-year=2025 : Start year for seeding}
                            {--end-year=2039 : End year for seeding}
                            {--force : Force seeding without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Seed public holidays from Calendarific API for specified countries and years';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🎉 Public Holiday Seeder Command');
        $this->info('This command will fetch holidays from Calendarific API and populate the database.');

        // Show current configuration
        $this->showConfiguration();

        // Confirm before proceeding (unless forced)
        if (!$this->option('force') && !$this->confirm('Do you want to proceed with seeding?')) {
            $this->info('Seeding cancelled.');
            return self::SUCCESS;
        }

        try {
            // Create and run the seeder
            $seeder = new CalendarificHolidaySeeder();

            // Pass options to seeder if needed
            $this->configureSeeder($seeder);

            $seeder->setCommand($this);
            $seeder->run();

            $this->info('✅ Holiday seeding completed successfully!');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error during seeding: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return self::FAILURE;
        }
    }

    /**
     * Show current configuration
     */
    private function showConfiguration(): void
    {
        $this->info("\n📋 Current Configuration:");
        $this->table(
            ['Setting', 'Value'],
            [
                ['API Key', config('services.calendarific.api_key') ? '✅ Configured' : '❌ Not configured'],
                ['Base URL', config('services.calendarific.base_url')],
                ['Countries', $this->option('countries') ? implode(', ', $this->option('countries')) : 'All target countries'],
                ['Years', $this->option('years') ? implode(', ', $this->option('years')) : $this->option('start-year') . ' - ' . $this->option('end-year')],
                ['Force Mode', $this->option('force') ? '✅ Enabled' : '❌ Disabled'],
            ]
        );
    }

    /**
     * Configure seeder with command options
     */
    private function configureSeeder(CalendarificHolidaySeeder $seeder): void
    {
        // If specific countries are provided, update the seeder
        if ($this->option('countries')) {
            $countries = $this->option('countries');
            if (is_string($countries)) {
                $countries = explode(',', $countries);
            }

            // Map country codes to names (simplified)
            $countryMap = [
                'EG' => 'Egypt',
                'SA' => 'Saudi Arabia',
                'JO' => 'Jordan',
                'SD' => 'Sudan',
                'AE' => 'United Arab Emirates',
                'KW' => 'Kuwait'
            ];

            $targetCountries = [];
            foreach ($countries as $code) {
                $code = strtoupper(trim($code));
                if (isset($countryMap[$code])) {
                    $targetCountries[$code] = $countryMap[$code];
                }
            }

            if (!empty($targetCountries)) {
                // Use reflection to set private property (for demonstration)
                $reflection = new \ReflectionClass($seeder);
                $property = $reflection->getProperty('targetCountries');
                $property->setAccessible(true);
                $property->setValue($seeder, $targetCountries);
            }
        }

        // If specific years are provided, update the seeder
        if ($this->option('years')) {
            $years = $this->option('years');
            if (is_string($years)) {
                $years = array_map('intval', explode(',', $years));
            }

            if (!empty($years)) {
                $startYear = min($years);
                $endYear = max($years);

                $reflection = new \ReflectionClass($seeder);

                $startProperty = $reflection->getProperty('startYear');
                $startProperty->setAccessible(true);
                $startProperty->setValue($seeder, $startYear);

                $endProperty = $reflection->getProperty('endYear');
                $endProperty->setAccessible(true);
                $endProperty->setValue($seeder, $endYear);
            }
        } else {
            // Use start-year and end-year options
            $startYear = (int) $this->option('start-year');
            $endYear = (int) $this->option('end-year');

            $reflection = new \ReflectionClass($seeder);

            $startProperty = $reflection->getProperty('startYear');
            $startProperty->setAccessible(true);
            $startProperty->setValue($seeder, $startYear);

            $endProperty = $reflection->getProperty('endYear');
            $endProperty->setAccessible(true);
            $endProperty->setValue($seeder, $endYear);
        }
    }
}
