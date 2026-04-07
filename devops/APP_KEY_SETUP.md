# APP_KEY Setup for DevOps

## Overview
The Laravel application requires an `APP_KEY` environment variable for encryption and security. This document explains how it's automatically handled in the deployment process.

## Automatic Generation
The `entrypoint.sh` script now automatically generates the `APP_KEY` if it's missing or invalid when the container starts.

## How It Works

### 1. `.env.example` Template
The `.env.example` file now includes:
```
APP_KEY=base64:PLACEHOLDER_WILL_BE_GENERATED_BY_ARTISAN
```

### 2. Automatic Generation in `entrypoint.sh`
When the container starts, the entrypoint script:
- Checks if `APP_KEY` exists in `.env`
- Checks if it's empty or contains the placeholder value
- Automatically runs `php artisan key:generate --force` if needed
- Logs the generation process

### 3. Detection Logic
The script detects missing/invalid APP_KEY in these cases:
- `APP_KEY=` (empty value)
- `APP_KEY=base64:PLACEHOLDER` (placeholder value)
- Missing `APP_KEY=base64:` prefix (invalid format)

## Manual Generation (If Needed)

If you need to manually generate the APP_KEY before deployment:

```bash
# Inside the container or locally
php artisan key:generate --force
```

## Environment Setup

### For New Deployments
1. Copy `.env.example` to `.env`
2. Configure database and other settings
3. Leave `APP_KEY` as placeholder or empty
4. Start the container - APP_KEY will be auto-generated

### For Existing Deployments
- If `.env` already has a valid `APP_KEY`, it will be preserved
- No action needed - the script only generates if missing/invalid

## Security Notes
- The `APP_KEY` is automatically generated with 32 random bytes
- Uses Laravel's secure key generation (`php artisan key:generate`)
- The key is base64 encoded and prefixed with `base64:`
- Once generated, the key persists in the `.env` file
- **Important**: Never commit the actual `.env` file to version control

## Troubleshooting

### Error: "No APP_KEY variable was found"
This error should no longer occur because:
- The entrypoint script generates it automatically
- The generation happens before any Laravel commands run

### Verify APP_KEY is Set
```bash
# Inside the container
grep APP_KEY /var/www/.env
```

### Force Regeneration
If you need to regenerate the key:
```bash
# Inside the container
php artisan key:generate --force
```

## Logs
The entrypoint script logs APP_KEY generation:
```
[2026-04-07 14:32:00] Checking APP_KEY...
[2026-04-07 14:32:01] APP_KEY not found or invalid. Generating new APP_KEY...
[2026-04-07 14:32:02] APP_KEY generated successfully
```

## CI/CD Integration
No special CI/CD configuration needed:
- The Docker container handles APP_KEY generation automatically
- Just ensure `.env` file is mounted/created with required variables
- APP_KEY will be generated on first container start

## Files Modified
- `devops/entrypoint.sh` - Added APP_KEY generation logic
- `.env.example` - Added APP_KEY placeholder
- `devops/APP_KEY_SETUP.md` - This documentation file
