<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Leave\PublicHoliday\Database\factories\PublicHolidayFactory;
use BasePackage\Shared\Traits\BaseFilterable;

//use BasePackage\Shared\Traits\HasTranslations;

class PublicHoliday extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'name_ar',
        'country_id',
        'country_code',
        'date_start',
        'date_end',
        'year',
        'holiday_type',
        'is_recurring',
        'description',
        'description_ar',
        'external_api_id',
        'api_data',
        'tags',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'country_id' => 'string',
        'date_start' => 'date',
        'date_end' => 'date',
        'year' => 'integer',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'api_data' => 'array',
        'tags' => 'array',
    ];

    /**
     * Get the country that owns the public holiday.
     */
    public function country()
    {
        return $this->belongsTo(\Modules\Country\Models\Country::class, 'country_id');
    }


    public function days(): HasMany
    {
        return $this->hasMany(PublicHolidayDay::class, 'public_holiday_id')->orderBy('date');
    }

    /**
     * Scope to get active holidays only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get holidays for a specific year
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope to get holidays for a specific country
     */
    public function scopeForCountry($query, string $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope to get holidays for a specific country code
     */
    public function scopeForCountryCode($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope to get holidays by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('holiday_type', $type);
    }

    /**
     * Scope to get holidays within date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date_start', [$startDate, $endDate])
              ->orWhereBetween('date_end', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('date_start', '<=', $startDate)
                     ->where('date_end', '>=', $endDate);
              });
        });
    }

    /**
     * Check if this holiday is on a specific date
     */
    public function isOnDate($date): bool
    {
        $checkDate = \Carbon\Carbon::parse($date);
        return $checkDate->between($this->date_start, $this->date_end);
    }

    /**
     * Get holiday duration in days
     */
    public function getDurationAttribute(): int
    {
        return $this->date_start->diffInDays($this->date_end) + 1;
    }

    /**
     * Get holiday name in specified language
     */
    public function getName(string $locale = 'en'): string
    {
        if ($locale === 'ar' && !empty($this->name_ar)) {
            return $this->name_ar;
        }
        return $this->name;
    }

    /**
     * Get holiday description in specified language
     */
    public function getDescription(string $locale = 'en'): ?string
    {
        if ($locale === 'ar' && !empty($this->description_ar)) {
            return $this->description_ar;
        }
        return $this->description;
    }

    /**
     * Get bilingual name (English and Arabic)
     */
    public function getBilingualName(): string
    {
        if (!empty($this->name_ar) && $this->name_ar !== $this->name) {
            return "{$this->name} ({$this->name_ar})";
        }
        return $this->name;
    }

    /**
     * Check if holiday has Arabic translation
     */
    public function hasArabicTranslation(): bool
    {
        return !empty($this->name_ar);
    }

    /**
     * Get localized attributes
     */
    public function getLocalizedAttributes(string $locale = 'en'): array
    {
        return [
            'name' => $this->getName($locale),
            'description' => $this->getDescription($locale),
        ];
    }

    protected static function newFactory(): PublicHolidayFactory
    {
        return PublicHolidayFactory::new();
    }
}
