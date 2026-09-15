<?php

declare(strict_types=1);

namespace Modules\Reports\Support;

/**
 * Formats punch metadata for the attendance report (clock-out cause + GPS).
 */
final class ReportPunchPresentation
{
    /**
     * @return array<string, array{ar: string, en: string}>
     */
    public static function causeLabels(): array
    {
        $radius = ['ar' => 'تلقائي — نصف القطر', 'en' => 'Auto — radius'];

        return [
            'manual'                  => ['ar' => 'الموظف',                    'en' => 'Employee'],
            'auto_max_ot'             => ['ar' => 'تلقائي — نهاية الوردية',     'en' => 'Auto — shift end'],
            'auto_next_shift'         => ['ar' => 'تلقائي — الوردية التالية',  'en' => 'Auto — next shift'],
            'auto_out_zone'           => ['ar' => 'تلقائي — خارج النطاق',      'en' => 'Auto — out of zone'],
            'auto_no_location'        => ['ar' => 'تلقائي — بدون موقع',        'en' => 'Auto — no GPS'],
            'auto_radius_enforcement' => $radius,
            'auto_radius'             => $radius,
            'auto_time_limit'         => ['ar' => 'تلقائي — حد الوقت',         'en' => 'Auto — time limit'],
        ];
    }

    public static function clockOutCauseCode(?string $shiftEndMethod, mixed $clockOutTime): string
    {
        if ($clockOutTime === null || $clockOutTime === '') {
            return '';
        }

        $method = is_string($shiftEndMethod) ? trim($shiftEndMethod) : '';

        return $method !== '' ? $method : 'manual';
    }

    public static function clockOutCauseLabel(string $code, string $lang): string
    {
        if ($code === '') {
            return '';
        }

        $labels = self::causeLabels();

        return $labels[$code][$lang] ?? $labels[$code]['en'] ?? $code;
    }

    public static function locationLabel(mixed $raw): string
    {
        $loc = self::decodeLocation($raw);
        if ($loc === []) {
            return '';
        }

        foreach (['address', 'formatted_address', 'name'] as $key) {
            $value = trim((string) ($loc[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $lat = $loc['latitude'] ?? $loc['lat'] ?? null;
        $lng = $loc['longitude'] ?? $loc['lng'] ?? $loc['lon'] ?? null;
        if (is_numeric($lat) && is_numeric($lng)) {
            return number_format((float) $lat, 5).', '.number_format((float) $lng, 5);
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeLocation(mixed $raw): array
    {
        if (is_object($raw)) {
            $raw = json_decode(json_encode($raw), true);
        }

        if (is_array($raw)) {
            return $raw;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
