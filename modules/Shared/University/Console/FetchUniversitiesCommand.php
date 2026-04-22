<?php

declare(strict_types=1);

namespace Modules\Shared\University\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Modules\Country\Models\Country;
use Modules\Shared\University\Models\University;

class FetchUniversitiesCommand extends Command
{
    protected $signature = 'universities:fetch
                            {--country= : Specific country name to fetch (optional)}
                            {--force : Force re-import even if universities exist}';

    protected $description = 'Fetch universities from Hipolabs API for all countries in database';

    public function handle(): int
    {
        $this->info('🎓 Starting university import from Hipolabs API...');

        $specificCountry = $this->option('country');
        $force = $this->option('force');

        if ($specificCountry) {
            $countries = Country::where('name', 'like', "%{$specificCountry}%")->get();

            if ($countries->isEmpty()) {
                $this->error("❌ No country found matching: {$specificCountry}");
                return self::FAILURE;
            }
        } else {
            $countries = Country::all();
        }

        if ($countries->isEmpty()) {
            $this->error('❌ No countries found in database');
            return self::FAILURE;
        }

        $this->info("📍 Found {$countries->count()} country/countries to process");

        $totalCreated = 0;
        $totalSkipped = 0;
        $totalFailed = 0;

        $progressBar = $this->output->createProgressBar($countries->count());
        $progressBar->start();

        foreach ($countries as $country) {
            $progressBar->advance();

            try {
                $result = $this->fetchUniversitiesForCountry($country, $force);

                $totalCreated += $result['created'];
                $totalSkipped += $result['skipped'];
                $totalFailed += $result['failed'];

            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("⚠️  Failed for {$country->name}: {$e->getMessage()}");
                $totalFailed++;
                continue;
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('✅ Import completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Created', $totalCreated],
                ['Skipped (duplicates)', $totalSkipped],
                ['Failed', $totalFailed],
            ]
        );

        return self::SUCCESS;
    }

    private function fetchUniversitiesForCountry(Country $country, bool $force): array
    {
        $created = 0;
        $skipped = 0;
        $failed = 0;

        try {
            $response = Http::timeout(30)
                ->get('http://universities.hipolabs.com/search', [
                    'country' => $country->name,
                ]);

            if (!$response->successful()) {
                return ['created' => 0, 'skipped' => 0, 'failed' => 1];
            }

            $universities = $response->json();

            if (empty($universities) || !is_array($universities)) {
                return ['created' => 0, 'skipped' => 0, 'failed' => 0];
            }

            foreach ($universities as $uniData) {
                if (empty($uniData['name'])) {
                    $failed++;
                    continue;
                }

                $name = $uniData['name'];
                $link = $uniData['web_pages'][0] ?? null;

                if (!$force) {
                    $exists = University::where('country_id', $country->id)
                        ->whereHas('translations', function ($q) use ($name) {
                            $q->where('locale', 'en')
                              ->where('field', 'name')
                              ->where('content', G);
                        })
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }
                }

                try {
                    University::create([
                        'name' => ['en' => $name, 'ar' => $name],
                        'country_id' => $country->id,
                        'link' => $link,
                    ]);

                    $created++;
                } catch (\Exception $e) {
                    $failed++;
                    \Log::error('Failed to create university', [
                        'country' => $country->name,
                        'university' => $name,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            \Log::error('Failed to fetch universities from API', [
                'country' => $country->name,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }
}
