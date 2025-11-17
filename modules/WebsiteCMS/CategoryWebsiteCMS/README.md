# CategoryWebsiteCMS Module

This module provides API endpoints for managing website categories with multilingual support (Arabic and English).

## Features

- Create, read, update, and delete categories
- Multilingual support (Arabic and English names)
- Category types (News, Articles, Products, Services, Events)
- Company-specific categories
- Export functionality

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/categories` | List all categories |
| GET | `/api/v1/categories/{id}` | Get category by ID |
| POST | `/api/v1/categories` | Create new category |
| PUT | `/api/v1/categories/{id}` | Update category |
| DELETE | `/api/v1/categories/{id}` | Delete category |
| POST | `/api/v1/categories/export` | Export categories |

## Request Parameters

### Create/Update Category

| Parameter | Type | Description |
|-----------|------|-------------|
| name_ar | string | Category name in Arabic (required, max 255 chars) |
| name_en | string | Category name in English (required, max 255 chars) |
| type_category_id | UUID | Type category ID (required, must exist in type_categories table) |

## Database Schema

### type_categories Table

- `id` (UUID, primary key)
- `name` (JSON) - Translatable name field
- `created_at` (timestamp)
- `updated_at` (timestamp)

### category_website_cms Table

- `id` (UUID, primary key)
- `name` (JSON) - Translatable name field
- `type_category_id` (UUID, foreign key to type_categories)
- `company_id` (foreign key to companies table)
- `created_at` (timestamp)
- `updated_at` (timestamp)

## Type Categories

The system comes with 5 predefined type categories:

1. **News** (أخبار)
2. **Articles** (مقالات)
3. **Products** (منتجات)
4. **Services** (خدمات)
5. **Events** (فعاليات)

## Seeding

To seed the type categories:

```bash
php artisan db:seed --class="Modules\WebsiteCMS\CategoryWebsiteCMS\Database\Seeders\TypeCategorySeeder"
```

## Models

### TypeCategory Model
- Uses `HasTranslations` trait for multilingual support
- Has one-to-many relationship with CategoryWebsiteCMS

### CategoryWebsiteCMS Model
- Uses `HasTranslations` trait for multilingual support
- Uses `BelongsToTenant` trait for multi-tenancy
- Belongs to TypeCategory
- Belongs to Company

## Testing

A Postman collection is provided in the `Resources/postman` directory for testing the API endpoints.

## Response Example

```json
{
    "id": "uuid-here",
    "name": {
        "ar": "أخبار التقنية",
        "en": "Technology News"
    },
    "name_ar": "أخبار التقنية",
    "name_en": "Technology News",
    "type_category_id": "uuid-here",
    "type_category": {
        "id": "uuid-here",
        "name": {
            "ar": "أخبار",
            "en": "News"
        },
        "name_ar": "أخبار",
        "name_en": "News"
    },
    "company_id": 1,
    "created_at": "2025-11-16T17:00:00.000000Z",
    "updated_at": "2025-11-16T17:00:00.000000Z"
}
```
