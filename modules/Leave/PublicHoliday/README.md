# Public Holiday Management System

A comprehensive system for managing public holidays across multiple countries using the Calendarific API.

## Features

- ✅ **Multi-Country Support**: Egypt, Saudi Arabia, Jordan, Sudan, UAE, Kuwait
- ✅ **15-Year Coverage**: 2025-2039 holiday data
- ✅ **API Integration**: Calendarific API for accurate holiday data
- ✅ **Holiday Types**: National, Local, Religious, Observance holidays
- ✅ **Smart Validation**: Working day calculations, holiday detection
- ✅ **Batch Processing**: Efficient seeding with rate limiting
- ✅ **Comprehensive Logging**: Full error tracking and reporting

## Installation & Setup

### 1. Run Database Migrations

```bash
php artisan migrate
```

### 2. Configure API Key

Add your Calendarific API key to your `.env` file:

```env
CALENDARIFIC_API_KEY=G2XGP0u6w8FihkwuRxUDzOjjT321JeJ5
CALENDARIFIC_BASE_URL=https://calendarific.com/api/v2
```

### 3. Seed Holiday Data

#### Option A: Using the Console Command (Recommended)

```bash
# Seed all target countries for 15 years (2025-2039)
php artisan holidays:seed

# Seed specific countries
php artisan holidays:seed --countries=EG,SA,JO

# Seed specific years
php artisan holidays:seed --years=2025,2026,2027

# Seed with custom year range
php artisan holidays:seed --start-year=2025 --end-year=2030

# Force seeding without confirmation
php artisan holidays:seed --force
```

#### Option B: Using Database Seeder

```bash
php artisan db:seed --class="Modules\Leave\PublicHoliday\Database\Seeders\CalendarificHolidaySeeder"
```

## Usage Examples

### Holiday Validation Service

```php
use Modules\Leave\PublicHoliday\Services\HolidayValidationService;
use Carbon\Carbon;

$holidayService = new HolidayValidationService();

// Check if October 6th, 2025 is a holiday in Egypt
$date = Carbon::create(2025, 10, 6);
$isHoliday = $holidayService->isHolidayByCountryCode($date, 'EG');

// Get next working day
$nextWorkingDay = $holidayService->getNextWorkingDay($date, $countryId);

// Count working days between dates
$workingDays = $holidayService->countWorkingDays(
    Carbon::create(2025, 10, 1),
    Carbon::create(2025, 10, 31),
    $countryId
);

// Get upcoming holidays
$upcomingHolidays = $holidayService->getUpcomingHolidays($countryId, 30);
```

### Model Queries

```php
use Modules\Leave\PublicHoliday\Models\PublicHoliday;

// Get all active holidays for Egypt in 2025
$holidays = PublicHoliday::active()
    ->forCountryCode('EG')
    ->forYear(2025)
    ->get();

// Get religious holidays only
$religiousHolidays = PublicHoliday::active()
    ->forCountryCode('SA')
    ->byType('religious')
    ->forYear(2025)
    ->get();

// Get holidays in date range
$holidays = PublicHoliday::active()
    ->inDateRange('2025-10-01', '2025-10-31')
    ->get();
```

## Database Schema

### Enhanced Fields

The `public_holidays` table includes these enhanced fields:

- `country_code`: ISO2 country code (EG, SA, JO, etc.)
- `year`: Holiday year for efficient querying
- `holiday_type`: Type classification (national, local, religious, observance, other)
- `is_recurring`: Whether holiday repeats annually
- `description`: Holiday description from API
- `external_api_id`: Calendarific API holiday UUID
- `api_data`: Raw API response data (JSON)
- `tags`: Holiday tags for categorization (JSON)
- `is_active`: Enable/disable holidays

### Indexes

Optimized indexes for performance:
- `country_id`, `year`, `is_active`
- `country_code`, `year`, `is_active`
- `holiday_type`, `is_active`
- `date_start`, `date_end`, `is_active`

## API Endpoints

### Get Holidays

```http
GET /api/v1/public-holidays
GET /api/v1/public-holidays?country_code=EG&year=2025
GET /api/v1/public-holidays?holiday_type=religious
```

### Holiday Validation

```http
GET /api/v1/public-holidays/check?date=2025-10-06&country_id={id}
GET /api/v1/public-holidays/upcoming?country_id={id}&days=30
```

## Target Countries

| Country | Code | Holidays Supported |
|---------|------|-------------------|
| Egypt | EG | ✅ National, Religious |
| Saudi Arabia | SA | ✅ National, Religious |
| Jordan | JO | ✅ National, Religious |
| Sudan | SD | ✅ National, Religious |
| UAE | AE | ✅ National, Religious |
| Kuwait | KW | ✅ National, Religious |

## Holiday Types

- **National**: Official government holidays
- **Religious**: Islamic holidays (Eid, Ramadan, etc.)
- **Local**: Region-specific holidays
- **Observance**: Cultural observances
- **Other**: Miscellaneous holidays

## API Rate Limits

The seeder includes built-in rate limiting:
- 1-second delay between API requests
- Batch processing for database operations
- Quota monitoring and warnings
- Error handling and retry logic

## Monitoring & Logging

### Check API Usage

```php
use Modules\Leave\PublicHoliday\Services\CalendarificApiService;

$apiService = new CalendarificApiService();
$usage = $apiService->checkApiUsage();

echo "Used: {$usage['quota_used']}";
echo "Remaining: {$usage['quota_remaining']}";
```

### Logs Location

- API errors: `storage/logs/laravel.log`
- Seeding progress: Console output
- Holiday validation: Application logs

## Troubleshooting

### Common Issues

1. **API Key Invalid**
   ```
   Solution: Verify CALENDARIFIC_API_KEY in .env file
   ```

2. **Quota Exceeded**
   ```
   Solution: Check API usage or upgrade Calendarific plan
   ```

3. **Country Not Found**
   ```
   Solution: Ensure country exists in countries table with correct iso2 code
   ```

4. **Migration Errors**
   ```
   Solution: Run migrations in order, check database permissions
   ```

### Debug Commands

```bash
# Check API connectivity
php artisan holidays:seed --countries=EG --years=2025 --force

# Verify database structure
php artisan migrate:status

# Check logs
tail -f storage/logs/laravel.log
```

## Performance Considerations

- **Indexing**: Comprehensive database indexes for fast queries
- **Caching**: Consider adding Redis/Memcached for frequently accessed holidays
- **Batch Processing**: Seeder uses batch inserts for efficiency
- **API Limits**: Built-in rate limiting respects Calendarific quotas

## Future Enhancements

- [ ] **Caching Layer**: Redis integration for holiday queries
- [ ] **More Countries**: Expand to additional Middle Eastern countries
- [ ] **Holiday Rules**: Custom business rules for holiday handling
- [ ] **Notifications**: Alert system for upcoming holidays
- [ ] **Calendar Integration**: Export to Google Calendar, Outlook
- [ ] **Multi-language**: Holiday names in Arabic and English

## Support

For issues or questions:
1. Check the logs for detailed error messages
2. Verify API key and quota status
3. Ensure database migrations are up to date
4. Review country code mappings in the database
