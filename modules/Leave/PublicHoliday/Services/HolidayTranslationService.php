<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Services;

class HolidayTranslationService
{
    /**
     * Holiday name translations (English => Arabic)
     */
    private array $translations = [
        // Islamic Holidays
        'Eid al-Fitr' => 'عيد الفطر',

        'Eid el Fitr' => 'عيد الفطر',
        'Eid al-Fitr Holiday' => 'إجازة عيد الفطر',

        'Eid al-Fitr holiday'=> 'إجازة عيد الفطر',
        'Eid el Fitr Holiday' => 'إجازة عيد الفطر',
        "Eid al-Adha holiday"=>"إجازة عيد الأضحى",
        'Eid al-Adha' => 'عيد الأضحى',
        'Eid al-Adha Holiday' => 'إجازة عيد الأضحى',
        'Arafat Day' => 'يوم عرفة',
        'Ramadan Start' => 'بداية رمضان',
        'Ramadan' => 'رمضان',
        'Prophet Mohamed\'s Birthday' => 'المولد النبوي الشريف',
        'Prophet Muhammad\'s Birthday' => 'المولد النبوي الشريف',
        'Mawlid' => 'المولد النبوي',
        'Muharram' => 'المحرم',
        'Ashura' => 'عاشوراء',
        'Islamic New Year' => 'رأس السنة الهجرية',
        'Hijri New Year' => 'رأس السنة الهجرية',
        "Flag Day"=>"عيد العلم",

        // Egyptian National Holidays
        'Revolution Day January 25' => 'ثورة 25 يناير',
        'Day off for Revolution Day January 25' => 'إجازة ثورة 25 يناير',
        'Revolution Day July 23' => 'ثورة 23 يوليو',
        'Armed Forces Day' => 'عيد القوات المسلحة',
        'Day off for Armed Forces Day' => 'إجازة عيد القوات المسلحة',
        'June 30 Revolution' => 'ثورة 30 يونيو',
        'Day off for June 30 Revolution' => 'إجازة ثورة 30 يونيو',
        'Sinai Liberation Day' => 'عيد تحرير سيناء',
        'Labor Day' => 'عيد العمال',
        'Spring Festival' => 'شم النسيم',
        "National Day"=>"اليوم الوطني",
        "Waqfat Arafat Day"=>"يوم وقفة عرفة",
        "Arafat (Hajj) Day"=>"يوم عرفة (حجة)",

        // Coptic/Christian Holidays
        'Coptic Christmas Day' => 'عيد الميلاد المجيد',
        'Coptic Christmas' => 'عيد الميلاد المجيد',
        'Coptic Easter Sunday' => 'عيد القيامة المجيد',
        'Coptic Easter' => 'عيد القيامة المجيد',
        'Coptic Good Friday' => 'الجمعة العظيمة',
        'Coptic Holy Saturday' => 'سبت النور',
        'Orthodox Easter' => 'عيد القيامة الأرثوذكسي',
        'Orthodox Christmas' => 'عيد الميلاد الأرثوذكسي',

        // Other Egyptian Holidays
        'Nayrouz' => 'النيروز',
        'Coptic New Year' => 'رأس السنة القبطية',
        'Flooding of the Nile' => 'وفاء النيل',

        // Saudi Arabian Holidays
        'Saudi National Day' => 'اليوم الوطني السعودي',
        'Founding Day' => 'يوم التأسيس',
        'Saudi Foundation Day' => 'يوم التأسيس السعودي',

        // Jordanian Holidays
        'Independence Day' => 'عيد الاستقلال',
        'Jordan Independence Day' => 'عيد استقلال الأردن',
        'King\'s Birthday' => 'عيد ميلاد الملك',
        'Arab Renaissance Day' => 'عيد النهضة العربية',

        // UAE Holidays
        'UAE National Day' => 'اليوم الوطني الإماراتي',
        'Commemoration Day' => 'يوم الشهيد',
        'Martyrs\' Day' => 'يوم الشهيد',

        // Kuwaiti Holidays
        'Kuwait National Day' => 'العيد الوطني الكويتي',
        'Liberation Day' => 'عيد التحرير',
        'Kuwait Liberation Day' => 'عيد تحرير الكويت',

        // Sudanese Holidays
        'Sudan Independence Day' => 'عيد استقلال السودان',
        'Unity Day' => 'يوم الوحدة',

        // Common/International Holidays
        'New Year\'s Day' => 'رأس السنة الميلادية',
        'International Workers\' Day' => 'عيد العمال العالمي',
        'May Day' => 'عيد العمال',
        'Christmas Day' => 'عيد الميلاد',
        'Good Friday' => 'الجمعة العظيمة',
        'Easter Sunday' => 'عيد القيامة',
        'Easter Monday' => 'اثنين الفصح',

        // Seasonal/Astronomical
        'March Equinox' => 'الاعتدال الربيعي',
        'June Solstice' => 'الانقلاب الصيفي',
        'September Equinox' => 'الاعتدال الخريفي',
        'December Solstice' => 'الانقلاب الشتوي',
        'Spring Equinox' => 'الاعتدال الربيعي',
        'Summer Solstice' => 'الانقلاب الصيفي',
        'Autumn Equinox' => 'الاعتدال الخريفي',
        'Winter Solstice' => 'الانقلاب الشتوي',
    ];

    /**
     * Additional patterns for dynamic translation
     */
    private array $patterns = [
        // Day off patterns
        '/^Day off for (.+)$/' => '��جازة $1',

        // Holiday patterns
        '/^(.+) Holiday$/' => 'إجازة $1',

        // Eid patterns
        '/^Eid (.+)$/' => 'عيد $1',

        // Revolution patterns
        '/^(.+) Revolution$/' => 'ثورة $1',

        // National Day patterns
        '/^(.+) National Day$/' => 'اليوم الوطني $1',

        // Birthday patterns
        '/^(.+) Birthday$/' => 'عيد ميلاد $1',
    ];

    /**
     * Get Arabic translation for a holiday name
     */
    public function getArabicName(string $englishName): string
    {
        // Direct translation lookup
        if (isset($this->translations[$englishName])) {
            return $this->translations[$englishName];
        }

        // Pattern-based translation
        foreach ($this->patterns as $pattern => $replacement) {
            if (preg_match($pattern, $englishName, $matches)) {
                // For simple replacements, try to translate the captured part
                if (isset($matches[1])) {
                    $translatedPart = $this->translations[$matches[1]] ?? $matches[1];
                    return str_replace('$1', $translatedPart, $replacement);
                }
                return preg_replace($pattern, $replacement, $englishName);
            }
        }

        // Fallback: return original name if no translation found
        return $englishName;
    }

    /**
     * Get both English and Arabic names
     */
    public function getBilingualNames(string $englishName): array
    {
        return [
            'en' => $englishName,
            'ar' => $this->getArabicName($englishName)
        ];
    }

    /**
     * Check if a holiday name has Arabic translation
     */
    public function hasTranslation(string $englishName): bool
    {
        return isset($this->translations[$englishName]) ||
               $this->matchesPattern($englishName);
    }

    /**
     * Get all available translations
     */
    public function getAllTranslations(): array
    {
        return $this->translations;
    }

    /**
     * Add or update a translation
     */
    public function addTranslation(string $english, string $arabic): void
    {
        $this->translations[$english] = $arabic;
    }

    /**
     * Get translation statistics
     */
    public function getTranslationStats(): array
    {
        return [
            'total_translations' => count($this->translations),
            'total_patterns' => count($this->patterns),
            'categories' => [
                'islamic' => $this->countByCategory(['Eid', 'Ramadan', 'Prophet', 'Muharram', 'Ashura', 'Arafat', 'Hijri']),
                'national' => $this->countByCategory(['Revolution', 'Independence', 'National', 'Liberation', 'Armed Forces']),
                'christian' => $this->countByCategory(['Coptic', 'Christmas', 'Easter', 'Orthodox']),
                'seasonal' => $this->countByCategory(['Equinox', 'Solstice']),
                'labor' => $this->countByCategory(['Labor', 'Workers']),
            ]
        ];
    }

    /**
     * Check if name matches any pattern
     */
    private function matchesPattern(string $name): bool
    {
        foreach ($this->patterns as $pattern => $replacement) {
            if (preg_match($pattern, $name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Count translations by category keywords
     */
    private function countByCategory(array $keywords): int
    {
        $count = 0;
        foreach ($this->translations as $english => $arabic) {
            foreach ($keywords as $keyword) {
                if (stripos($english, $keyword) !== false) {
                    $count++;
                    break;
                }
            }
        }
        return $count;
    }

    /**
     * Format holiday name for display
     */
    public function formatForDisplay(string $englishName, string $locale = 'en'): string
    {
        if ($locale === 'ar') {
            return $this->getArabicName($englishName);
        }

        if ($locale === 'both') {
            $arabic = $this->getArabicName($englishName);
            return $arabic !== $englishName ? "{$englishName} ({$arabic})" : $englishName;
        }

        return $englishName;
    }

    /**
     * Get holiday names for a specific country with cultural context
     */
    public function getLocalizedNamesForCountry(string $countryCode, string $englishName): array
    {
        $result = $this->getBilingualNames($englishName);

        // Add country-specific context
        switch (strtoupper($countryCode)) {
            case 'EG':
                $result['country_context'] = 'مصر';
                break;
            case 'SA':
                $result['country_context'] = 'السعودية';
                break;
            case 'JO':
                $result['country_context'] = 'الأردن';
                break;
            case 'AE':
                $result['country_context'] = 'الإمارات';
                break;
            case 'KW':
                $result['country_context'] = 'الكويت';
                break;
            case 'SD':
                $result['country_context'] = 'السودان';
                break;
        }

        return $result;
    }
}
