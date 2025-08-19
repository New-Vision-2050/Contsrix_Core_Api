<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Commands;

use Illuminate\Console\Command;
use Modules\Leave\PublicHoliday\Services\HolidayTranslationService;
use Modules\Leave\PublicHoliday\Services\CalendarificApiService;

class TestTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'holidays:test-translations
                            {country=EG : Country code to test}
                            {year=2027 : Year to test}';

    /**
     * The console command description.
     */
    protected $description = 'Test Arabic translations for holiday names';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $country = $this->argument('country');
        $year = (int) $this->argument('year');

        $this->info("��� Testing Arabic Translations");
        $this->info("Country: {$country}");
        $this->info("Year: {$year}");

        // Test translation service
        $translationService = new HolidayTranslationService();

        $this->info("\n📊 Translation Statistics:");
        $stats = $translationService->getTranslationStats();
        $this->table(
            ['Category', 'Count'],
            [
                ['Total Translations', $stats['total_translations']],
                ['Total Patterns', $stats['total_patterns']],
                ['Islamic Holidays', $stats['categories']['islamic']],
                ['National Holidays', $stats['categories']['national']],
                ['Christian Holidays', $stats['categories']['christian']],
                ['Seasonal Events', $stats['categories']['seasonal']],
                ['Labor Related', $stats['categories']['labor']],
            ]
        );

        // Test with real API data
        $this->info("\n🔍 Testing with real holiday data...");

        try {
            $apiService = new CalendarificApiService();
            $holidays = $apiService->getHolidays($country, $year);

            if ($holidays->isEmpty()) {
                $this->error("No holidays found for {$country} {$year}");
                return self::FAILURE;
            }

            $this->info("Found {$holidays->count()} holidays. Testing translations...");

            $translationResults = [];
            $translatedCount = 0;
            $untranslatedCount = 0;

            foreach ($holidays as $holiday) {
                $englishName = $holiday['name'];
                $arabicName = $holiday['name_ar'];
                $hasTranslation = !empty($arabicName);

                if ($hasTranslation) {
                    $translatedCount++;
                } else {
                    $untranslatedCount++;
                }

                $translationResults[] = [
                    $englishName,
                    $arabicName ?? 'No translation',
                    $hasTranslation ? '✅' : '❌',
                    $holiday['holiday_type']
                ];
            }

            $this->info("\n🎯 Translation Results:");
            $this->info("Translated: {$translatedCount}");
            $this->info("Untranslated: {$untranslatedCount}");
            $this->info("Success Rate: " . round(($translatedCount / $holidays->count()) * 100, 1) . "%");

            $this->info("\n📋 Holiday Translations:");
            $this->table(
                ['English Name', 'Arabic Name', 'Status', 'Type'],
                $translationResults
            );

            // Show some examples of specific translations
            $this->info("\n🌟 Notable Translations:");
            $notableHolidays = [
                'Armed Forces Day' => 'عيد القوات المسلحة',
                'Eid al-Fitr' => 'عيد الفطر',
                'Revolution Day January 25' => 'ثورة 25 يناير',
                'Prophet Mohamed\'s Birthday' => 'المولد النبوي الشريف',
                'Coptic Christmas Day' => 'عيد الميلاد المجيد'
            ];

            foreach ($notableHolidays as $english => $expectedArabic) {
                $actualArabic = $translationService->getArabicName($english);
                $match = $actualArabic === $expectedArabic ? '✅' : '❌';
                $this->line("  {$match} {$english} → {$actualArabic}");
            }

            // Test pattern matching
            $this->info("\n🔧 Testing Pattern Matching:");
            $testPatterns = [
                'Day off for Armed Forces Day',
                'Eid al-Fitr Holiday',
                'June 30 Revolution',
                'Saudi National Day'
            ];

            foreach ($testPatterns as $testName) {
                $translation = $translationService->getArabicName($testName);
                $hasPattern = $translationService->hasTranslation($testName);
                $status = $hasPattern ? '✅' : '❌';
                $this->line("  {$status} {$testName} → {$translation}");
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error testing translations: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
