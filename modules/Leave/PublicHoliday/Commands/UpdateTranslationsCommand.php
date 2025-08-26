<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Commands;

use Illuminate\Console\Command;
use Modules\Leave\PublicHoliday\Models\PublicHoliday;
use Modules\Leave\PublicHoliday\Services\HolidayTranslationService;

class UpdateTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'holidays:update-translations
                            {--country=* : Specific country codes to update (e.g., EG,SA,JO)}
                            {--year=* : Specific years to update (e.g., 2025,2026)}
                            {--force : Force update even if Arabic name already exists}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Update existing holidays with Arabic translations';

    private HolidayTranslationService $translationService;

    public function __construct()
    {
        parent::__construct();
        $this->translationService = new HolidayTranslationService();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🌍 Updating Holiday Translations');

        // Build query based on options
        $query = PublicHoliday::query();

        // Filter by countries if specified
        if ($this->option('country')) {
            $countries = is_array($this->option('country'))
                ? $this->option('country')
                : explode(',', $this->option('country')[0]);

            $countries = array_map('strtoupper', array_map('trim', $countries));
            $query->whereIn('country_code', $countries);
            $this->info("🏳️  Filtering by countries: " . implode(', ', $countries));
        }

        // Filter by years if specified
        if ($this->option('year')) {
            $years = is_array($this->option('year'))
                ? $this->option('year')
                : explode(',', $this->option('year')[0]);

            $years = array_map('intval', $years);
            $query->whereIn('year', $years);
            $this->info("📅 Filtering by years: " . implode(', ', $years));
        }

        // Filter by holidays without Arabic names (unless force is used)
        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('name_ar')
                  ->orWhere('name_ar', '');
            });
            $this->info("🔍 Only updating holidays without Arabic translations");
        } else {
            $this->info("⚡ Force mode: Updating all matching holidays");
        }

        $holidays = $query->get();

        if ($holidays->isEmpty()) {
            $this->warn("⚠️  No holidays found matching the criteria");
            return self::SUCCESS;
        }

        $this->info("📊 Found {$holidays->count()} holidays to process");

        $stats = [
            'total' => $holidays->count(),
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'by_country' => [],
            'by_type' => [],
        ];

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn("🧪 DRY RUN MODE - No changes will be made");
        }

        // Process holidays in chunks to avoid memory issues
        $holidays->chunk(100)->each(function ($chunk) use (&$stats, $isDryRun) {
            foreach ($chunk as $holiday) {
                $this->processHoliday($holiday, $stats, $isDryRun);
            }
        });

        // Display results
        $this->displayResults($stats, $isDryRun);

        return self::SUCCESS;
    }

    /**
     * Process a single holiday
     */
    private function processHoliday(PublicHoliday $holiday, array &$stats, bool $isDryRun): void
    {
        try {
            $englishName = $holiday->name;
            $currentArabicName = $holiday->name_ar;

            // Get Arabic translation
            $arabicName = $this->translationService->getArabicName($englishName);

            // Skip if no translation found or same as English
            if ($arabicName === $englishName) {
                $stats['skipped']++;
                $this->line("  ⏭️  Skipped: {$englishName} (no translation available)", 'comment');
                return;
            }

            // Skip if already has Arabic name and not forcing
            if (!$this->option('force') && !empty($currentArabicName)) {
                $stats['skipped']++;
                $this->line("  ⏭️  Skipped: {$englishName} (already has Arabic name)", 'comment');
                return;
            }

            // Update statistics
            $countryCode = $holiday->country_code ?? 'unknown';
            $holidayType = $holiday->holiday_type ?? 'unknown';

            $stats['by_country'][$countryCode] = ($stats['by_country'][$countryCode] ?? 0) + 1;
            $stats['by_type'][$holidayType] = ($stats['by_type'][$holidayType] ?? 0) + 1;

            if (!$isDryRun) {
                // Update the holiday
                $holiday->update([
                    'name_ar' => $arabicName
                ]);
            }

            $stats['updated']++;

            $status = $isDryRun ? '[DRY RUN]' : '✅';
            $this->line("  {$status} {$englishName} → {$arabicName} ({$countryCode})", 'info');

        } catch (\Exception $e) {
            $stats['errors']++;
            $this->line("  ❌ Error processing {$holiday->name}: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Display final results
     */
    private function displayResults(array $stats, bool $isDryRun): void
    {
        $this->info("\n" . str_repeat('=', 60));
        $this->info($isDryRun ? "🧪 DRY RUN RESULTS" : "✅ UPDATE COMPLETED");
        $this->info(str_repeat('=', 60));

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $stats['total']],
                ['Updated', $stats['updated']],
                ['Skipped', $stats['skipped']],
                ['Errors', $stats['errors']],
                ['Success Rate', $stats['total'] > 0 ? round(($stats['updated'] / $stats['total']) * 100, 1) . '%' : '0%'],
            ]
        );

        if (!empty($stats['by_country'])) {
            $this->info("\n📊 Updates by Country:");
            $countryTable = [];
            foreach ($stats['by_country'] as $country => $count) {
                $countryTable[] = [$country, $count];
            }
            $this->table(['Country', 'Updated'], $countryTable);
        }

        if (!empty($stats['by_type'])) {
            $this->info("\n🏷️  Updates by Holiday Type:");
            $typeTable = [];
            foreach ($stats['by_type'] as $type => $count) {
                $typeTable[] = [ucfirst($type), $count];
            }
            $this->table(['Type', 'Updated'], $typeTable);
        }

        if ($stats['errors'] > 0) {
            $this->warn("\n⚠️  {$stats['errors']} errors occurred during processing. Check the output above for details.");
        }

        if ($isDryRun && $stats['updated'] > 0) {
            $this->info("\n💡 To apply these changes, run the command without --dry-run");
        }
    }
}
