# WebsiteContactMessage Module

## Overview
The WebsiteContactMessage module manages contact form submissions from website visitors. This module is part of the WebsiteCMS package and handles storing, retrieving, and managing contact messages.

## Features
- Create contact messages from website forms
- List and filter contact messages
- Update message status
- Export messages to Excel/CSV
- Multi-tenant support (company-scoped)

## Database Schema

### Table: `website_contact_messages`

| Column | Type | Description |
|--------|------|-------------|
| id | UUID | Primary key |
| name | VARCHAR(255) | Contact person name |
| phone | VARCHAR(20) | Contact phone number |
| email | VARCHAR(255) | Contact email address |
| address | VARCHAR(500) | Contact address (nullable) |
| status | INTEGER | Message status (0=Inactive, 1=Active) |
| message | TEXT | Contact message content |
| company_id | UUID | Foreign key to companies table |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

### Indexes
- Primary key on `id`
- Foreign key on `company_id` (references companies.id, cascade on delete)
- Index on `company_id`
- Index on `status`
- Index on `email`

## API Endpoints

All endpoints are prefixed with `/api/v1/website-contact-messages`

### List Messages
**GET** `/`

Query Parameters:
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default: 15)
- `name` (string): Filter by name (partial match)
- `phone` (string): Filter by phone (partial match)
- `email` (string): Filter by email (partial match)
- `status` (integer): Filter by status (0 or 1)
- `message` (string): Filter by message content (partial match)

### Create Message
**POST** `/`

Request Body (form-data):
- `name` (required, string, max 255): Contact name
- `phone` (required, string, max 20): Contact phone
- `email` (required, email, max 255): Contact email
- `address` (nullable, string, max 500): Contact address
- `status` (nullable, integer, 0 or 1): Message status (default: 0)
- `message` (required, string): Contact message

### Get Message
**GET** `/{id}`

Parameters:
- `id` (UUID): Message ID

### Update Message
**PUT** `/{id}`

Parameters:
- `id` (UUID): Message ID

Request Body (form-data):
- `name` (nullable, string, max 255): Contact name
- `phone` (nullable, string, max 20): Contact phone
- `email` (nullable, email, max 255): Contact email
- `address` (nullable, string, max 500): Contact address
- `status` (nullable, integer, 0 or 1): Message status
- `message` (nullable, string): Contact message

### Delete Message
**DELETE** `/{id}`

Parameters:
- `id` (UUID): Message ID

### Export Messages
**POST** `/export`

Request Body (JSON):
```json
{
  "format": "xlsx",
  "ids": []
}
```

- `format` (string): Export format (xlsx or csv)
- `ids` (array): Array of message IDs to export (empty = export all)

## Module Structure

```
WebsiteContactMessage/
├── Commands/
│   └── UpdateWebsiteContactMessageCommand.php
├── Controllers/
│   └── WebsiteContactMessageController.php
├── Database/
│   ├── factories/
│   │   └── WebsiteContactMessageFactory.php
│   └── migrations/
│       └── 2025_11_30_180000_create_website_contact_messages_table.php
├── DTO/
│   └── CreateWebsiteContactMessageDTO.php
├── Exports/
│   └── WebsiteContactMessageExport.php
├── Filters/
│   └── WebsiteContactMessageFilter.php
├── Handlers/
│   ├── DeleteWebsiteContactMessageHandler.php
│   └── UpdateWebsiteContactMessageHandler.php
├── Models/
│   └── WebsiteContactMessage.php
├── Presenters/
│   └── WebsiteContactMessagePresenter.php
├── Providers/
│   └── WebsiteContactMessageServiceProvider.php
├── Repositories/
│   └── WebsiteContactMessageRepository.php
├── Requests/
│   ├── CreateWebsiteContactMessageRequest.php
│   ├── DeleteWebsiteContactMessageRequest.php
│   ├── ExportWebsiteContactMessageRequest.php
│   ├── GetWebsiteContactMessageListRequest.php
│   ├── GetWebsiteContactMessageRequest.php
│   └── UpdateWebsiteContactMessageRequest.php
├── Resources/
│   └── routes/
│       └── api.php
├── Services/
│   └── WebsiteContactMessageCRUDService.php
└── module.json
```

## Usage Examples

### Create a Contact Message
```bash
curl -X POST http://localhost/api/v1/website-contact-messages \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -F "name=John Doe" \
  -F "phone=+1234567890" \
  -F "email=john@example.com" \
  -F "address=123 Main St" \
  -F "status=0" \
  -F "message=I would like to inquire about your services"
```

### List Messages with Filters
```bash
curl -X GET "http://localhost/api/v1/website-contact-messages?status=0&page=1&per_page=15" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Update Message Status
```bash
curl -X PUT http://localhost/api/v1/website-contact-messages/{id} \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -F "status=1"
```

### Export Messages
```bash
curl -X POST http://localhost/api/v1/website-contact-messages/export \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"format":"xlsx","ids":[]}'
```

## Postman Collection

A complete Postman collection is available at:
`WebsiteContactMessage_API.postman_collection.json`

Import this collection into Postman for easy API testing.

## Notes

- All endpoints require authentication via Bearer token
- The module is tenant-aware and automatically scopes data to the current company
- No translation support - all fields are stored as plain text
- Status field uses integer: 0 (Inactive/Unread) or 1 (Active/Read)
- Export functionality supports both XLSX and CSV formats
