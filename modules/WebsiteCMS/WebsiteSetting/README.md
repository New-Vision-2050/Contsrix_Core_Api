# WebsiteSetting Module

This module provides API endpoints for managing website settings such as colors, logo, and website address.

## Features

- Create, read, update, and delete website settings
- Upload and manage website logo
- Store color schemes (main color, second color, background color)
- Store website address
- Company-specific settings
- Export functionality

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/website_settings` | List all website settings |
| GET | `/api/v1/website_settings/current` | Get current company's website settings |
| POST | `/api/v1/website_settings/current` | Update current company's website settings |
| GET | `/api/v1/website_settings/{id}` | Get website setting by ID |
| POST | `/api/v1/website_settings` | Create new website setting |
| PUT | `/api/v1/website_settings/{id}` | Update website setting |
| DELETE | `/api/v1/website_settings/{id}` | Delete website setting |
| POST | `/api/v1/website_settings/export` | Export website settings |

## Request Parameters

### Create/Update Website Setting

| Parameter | Type | Description |
|-----------|------|-------------|
| main_color | string | Main color in hex format (e.g., #FF5733) |
| second_color | string | Secondary color in hex format (e.g., #33FF57) |
| background_color | string | Background color in hex format (e.g., #5733FF) |
| logo | file | Website logo image file (jpeg, png, jpg, gif, svg) |
| website_address | string | Website URL (e.g., https://example.com) |

## Database Schema

The module uses the `website_settings` table with the following structure:

- `id` (UUID, primary key)
- `main_color` (string, nullable)
- `second_color` (string, nullable)
- `background_color` (string, nullable)
- `logo` (string, nullable) - Stores file path, actual file handled by media library
- `website_address` (string, nullable)
- `company_id` (foreign key to companies table)
- `created_at` (timestamp)
- `updated_at` (timestamp)

## File Upload

Logo uploads are handled by the `WebsiteSettingUploadService` which uses the `FileUploadService` from the Shared Media module. Files are stored in the `website-settings/logos` directory.

## Testing

A Postman collection is provided in the `Resources/postman` directory for testing the API endpoints.
