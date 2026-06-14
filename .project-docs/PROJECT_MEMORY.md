# Contsrix_Core_Api — Project Memory

> Consolidated knowledge, patterns, gotchas, and rules. Update as the project evolves.

## Quick Reference

| Key | Value |
|---|---|
| **Company** | Vision |
| **Repo** | https://github.com/New-Vision-2050/Contsrix_Core_Api |
| **Local** | `/Users/dev.desoky/.openclaw/workspace/projects/vision/Contsrix_Core_Api` |
| **Stack** | Laravel 11, PHP 8.2, RoadRunner Octane, RabbitMQ |
| **Tenancy** | stancl/tenancy 3.x (tenant = Company model) |
| **Auth** | JWT (tymon/jwt-auth) + OTP fallback |
| **Permissions** | Spatie RBAC + route-level `->permission()` macro |

## Completed Analysis (2026-05-10)

- Full project exploration and architecture documentation
- Module inventory: 32 top-level, 129 total module.json files
- Ecommerce: 29 sub-modules identified
- Shared: 27 sub-modules identified
- Key patterns documented: Controller→Service→Repository→Model, DTOs, Presenters, Command/Handler
- Tech debt and missing items cataloged
- Module creation template documented

## Key Patterns

### Request Flow
```
Route → Controller → FormRequest (validate + toDto) → Service → Repository → Model
                                                                          ↓
                                JSON ← Presenter ← Controller ←————————————┘
```

### For Complex Operations
```
Route → Controller → Command DTO → Handler → (multiple Services/Repositories)
```

### Module Registration
- Each module has a `module.json` with `providers` array
- Provider extends `BasePackage\Shared\Module\ModuleServiceProvider`
- Auto-discovered — no manual registration in `config/app.php`

## Gotchas

1. **Tenant bootstrappers ALL commented out** in `config/tenancy.php` — manual scoping required everywhere
2. **helpers.php is ~3,000 lines** — check before writing utility functions
3. **Nested modules have inconsistent namespaces** — some use `Modules`, others `Modules\Parent`
4. **No root `routes/api.php`** — all routes in module `Resources/routes/api.php`
5. **ProdactInfo has no module.json** — module may not auto-load
6. **Company is the tenant model** — `config/tenancy.php` → `tenant_model = Company::class`
7. **UUIDs everywhere** — models use `UuidTrait`, PKs are strings not integers
8. **Auth routes are tenant-scoped** — `InitializeTenancyByRequestData` on auth endpoints

## Rules (Learned)

- ⛔ **No unit tests or feature tests in Vision.** Do not write or add any tests.
- When creating a new entity, follow the full module pattern: DTO → Request → Repository → Service → Presenter → Controller → Routes
- Always include `InitializeTenancyByRequestData` middleware on tenant-scoped API routes
- Use `$request->toDto()` pattern for passing validated data to services
- Presenters use static methods: `::present($model)` and `::collection($models)`
- DTOs use PHP 8.2 constructor promotion with `toArray()` method
- Repositories extend `BaseRepository` from base-package
- Permissions defined as enums, checked via `->permission()` route macro

## File Locations Quick Guide

| What | Where |
|---|---|
| Tenant config | `config/tenancy.php` |
| Auth config | `config/auth.php` |
| User model | `modules/User/Models/User.php` |
| Company (tenant) model | `modules/Company/CompanyCore/Models/Company.php` |
| Base controller | `app/Http/Controllers/Controller.php` |
| Base repository | `vendor/m-tech-stack/base-package/src/Shared/Repositories/BaseRepository.php` |
| Module base provider | `vendor/m-tech-stack/base-package/src/Shared/Module/ModuleServiceProvider.php` |
| Global helpers | `app/helpers.php` |
| Root migrations | `database/migrations/` |
| Module migrations | `modules/*/Database/Migrations/` |
| API routes | `modules/*/Resources/routes/api.php` |

## Module Dependency Graph

```
User → Company, RoleAndPermission
Attendance → User, Company, RoleAndPermission
Reports → User, Company, RoleAndPermission, Attendance
CompanyUser → User, Company
UserInfo → User
Leave → (depends on User, Company via hierarchy)
Subscription → Company
```
