# Understanding the Data Column in stancl/tenancy

This document explains how the stancl/tenancy package uses the `data` column to store tenant attributes.

## Overview

The stancl/tenancy package uses a JSON column called `data` to store tenant attributes instead of using separate columns for each attribute. This is done using the `VirtualColumn` trait from the `stancl/virtualcolumn` package.

## How It Works

1. The `Tenant` model extends `Stancl\Tenancy\Database\Models\Tenant` which uses the `HasDataColumn` trait.
2. The `HasDataColumn` trait uses the `VirtualColumn` trait which serializes attributes that don't exist as columns on the model's table into a JSON column named `data`.
3. When you set an attribute on a `Tenant` model, the attribute is stored in the `data` column if it's not in the list of custom columns.

## Database Schema

The `tenants` table has the following structure:

```sql
CREATE TABLE `tenants` (
  `id` varchar(255) NOT NULL,
  `company_id` char(36) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `data` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenants_company_id_foreign` (`company_id`),
  CONSTRAINT `tenants_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
)
```

Even though `company_id` and `name` are defined as columns in the table, the actual values are stored in the `data` column because they are not defined in the `getCustomColumns()` method of the `Tenant` model.

## Custom Columns

The `Tenant` model defines which columns are stored directly in the database table and which ones are stored in the `data` JSON column using the `getCustomColumns()` method:

```php
public static function getCustomColumns(): array
{
    return [
        'id',
        'created_at',
        'updated_at',
        'data',
        'password',
        // Note: company_id and name are stored in the data column
    ];
}
```

Any attribute not listed in the `getCustomColumns()` method will be stored in the `data` column.

## Accessing Attributes

When you access an attribute on a `Tenant` model, the `VirtualColumn` trait automatically retrieves the attribute from the `data` column if it's not a custom column.

```php
$tenant = Tenant::find('tenant-id');
$companyId = $tenant->company_id; // Retrieved from the data column
$name = $tenant->name; // Retrieved from the data column
```

## Querying Attributes in the Data Column

When querying attributes that are stored in the `data` column, you need to use the `->` operator to access the JSON properties:

```php
// Find a tenant by company_id
$tenant = Tenant::where('data->company_id', $companyId)->first();
```

## Creating Tenants

When creating a tenant, you can set attributes that will be stored in the `data` column:

```php
$tenant = Tenant::create([
    'id' => $company->id,
    'company_id' => $company->id, // This will be stored in the data column
    'name' => $company->name,     // This will be stored in the data column
]);
```

## Conclusion

Understanding how the `data` column works is important for working with the stancl/tenancy package. By default, most attributes are stored in the `data` column, not in separate columns. This is why the `company_id` field is empty in the database but the data is stored in the `data` column.