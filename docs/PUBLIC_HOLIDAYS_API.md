# Recurring public holidays

All endpoints use the existing bearer authentication, company/tenant context and public holiday permissions.

## Create and update

`POST /api/v1/public-holidays` or `PUT /api/v1/public-holidays/{id}`:

```json
{
  "name": "إجازة رسمية",
  "branch_id": 12,
  "date_start": "09-24",
  "date_end": "09-26"
}
```

Dates are **MM-DD**, with no input year. The branch is a `management_hierarchies` row of type `branch` belonging to the current company. Creation stores the first occurrence in the current year; February 29 uses the next valid leap year when necessary. Update preserves the occurrence's original year and rejects dates that do not exist in that year. An end month/day earlier than the start means the holiday ends in the next year (e.g. `12-31` to `01-02`).

Responses use the existing `payload` envelope. Holiday objects expose `branch_id`, `branch: {id, name}`, `date_start`/`date_end` as MM-DD, `year`, `is_recurring`, `count_days`, and `days`. Applied `days[].date` remains YYYY-MM-DD; `count_days` includes compensation days. Existing compensation and weekday-shifting rules remain in effect.

## List and detail

- `GET /api/v1/public-holidays?branch_id=12&year=2026`
- `GET /api/v1/public-holidays?branch_id=12&year=2026&month=9`
- `GET /api/v1/public-holidays?branch_id=12&year=2026&date_start=09-24`
- `GET /api/v1/public-holidays/{id}`

Listing defaults to the current year; month requires year. Period filtering includes holidays whose date ranges overlap the requested month/year. Optional `date_start` and `date_end` filters match exact MM-DD values. Pagination uses `page` and `per_page` (maximum 100).

## Branch cards

`GET /api/v1/public-holidays/branches`

```json
{
  "code": "SUCCESS_WITH_LIST_PAYLOAD_OBJECTS",
  "message": null,
  "payload": [
    {"branch_id": 12, "name": "فرع جدة", "years": [2025, 2026]},
    {"branch_id": 15, "name": "فرع الرياض", "years": []}
  ]
}
```

Returns only the current company's branches, including branches without holidays. Years are distinct and ascending, from stored holiday periods. Clicking a year uses the list endpoint with `branch_id` and `year`. Card background imagery belongs to the frontend; branches have no dedicated image source. The warning icon is omitted as requested.

## Annual generation and deployment

Apply the new migrations before using these endpoints. The existing Laravel scheduler now runs `public-holidays:generate-year` every January 1 at 00:01, Asia/Riyadh. The server must already run Laravel's `schedule:run` every minute; adding a schedule in code does not install a server cron entry.

For a manual run or recovery:

```sh
php artisan public-holidays:generate-year --year=2027
```

The command copies active, recurring branch holidays from their original records, recalculates applied/compensation days for the target year, and makes them available to live attendance holiday resolution. Original records remain the recurrence templates; generated occurrences do not become new templates. Repeated runs create no duplicates. February 29 is skipped in non-leap years. Historical country holidays remain stored and are not copied by this command.
