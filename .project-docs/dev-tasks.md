# Contsrix_Core_Api — Development Tasks & Tech Debt

## 1. Architecture Issues

### ⚠️ Tenant Bootstrappers All Commented Out
- **File:** `config/tenancy.php`
- **Issue:** All bootstrappers disabled — database, cache, filesystem, queue, redis
- **Impact:** No automatic tenant scoping. Every query must be manually scoped.
- **Risk:** Easy to leak data across tenants if developer forgets to scope.
- **Recommendation:** Enable `DatabaseTenancyBootstrapper` at minimum, or establish strict repository-level scoping patterns.

### ⚠️ 3000-Line helpers.php
- **File:** `app/helpers.php`
- **Issue:** Massive global helper file with no organization
- **Impact:** Hard to discover functions, potential naming conflicts, tight coupling
- **Recommendation:** Extract into dedicated service classes or per-module helpers over time.

### ⚠️ Inconsistent Namespace Pattern
- Some nested modules use `namespace Modules` (CompanyCore)
- Others use `namespace Modules\ParentName` (CompanyField, Ecommerce sub-modules)
- **Recommendation:** Standardize on one pattern.

## 2. Missing Module Configurations

### Missing module.json Files
| Module | Path | Status |
|---|---|---|
| ProdactInfo | `modules/ProdactInfo/` | No module.json — may not auto-load |
| Company | `modules/Company/` | No parent module.json (has sub-modules) |
| Ecommerce | `modules/Ecommerce/` | No parent module.json (has sub-modules) |
| Shared | `modules/Shared/` | No parent module.json (has sub-modules) |
| UserInfo | `modules/UserInfo/` | No parent module.json (has sub-modules) |
| WebsiteCMS | `modules/WebsiteCMS/` | No parent module.json (has sub-modules) |
| Leave | `modules/Leave/` | No parent module.json (has sub-modules) |
| Subscription | `modules/Subscription/` | No parent module.json (has sub-modules) |
| Project | `modules/Project/` | No parent module.json (has sub-modules) |
| ArchiveLibrary | `modules/ArchiveLibrary/` | No parent module.json (has sub-modules) |

Note: For nested modules, parent module.json may be optional if sub-modules self-register. But `ProdactInfo` has no sub-modules and no module.json — this is a real gap.

### Missing Route Files
| Module | Issue |
|---|---|
| Several Shared sub-modules | No `Resources/routes/api.php` — these are reference data modules, may not need routes |

## 3. Code Quality

### Large Files (potential refactor candidates)
- `app/helpers.php` — 2,973 lines
- Attendance module likely has large service/handler files
- Reports module likely has complex report generation logic

### Duplicate Code Patterns
- CRUD operations repeated across many modules
- Standard list/create/update/delete presenters are nearly identical
- Consider a generic CRUD presenter base class

### Missing Tests
- Most modules lack dedicated test directories
- Only CompanyCore has a `tests/` directory
- No evidence of comprehensive integration/feature tests

## 4. Security Considerations

### ✅ Strengths
- JWT auth with token expiration
- Spatie RBAC for fine-grained permissions
- Route-level permission checks
- UUID PKs (prevents ID enumeration)
- Full audit trail on critical models
- OTP-based 2FA for sensitive operations

### ⚠️ Concerns
- Manual tenant scoping (easy to miss)
- helpers.php global functions may have undiscovered vulnerabilities
- Admin impersonation (`login-as-admin`) — ensure proper logging/restrictions
- Throttle on auth routes (`throttle:35,1`) — consider stricter rate limiting

### 🔍 Review Needed
- Tenant data isolation in all repository queries
- File upload security / MIME type validation
- OTP brute force protection
- Cross-tenant data access via Shareable trait

## 5. Performance

### Potential Bottlenecks
- 3,000-line helpers.php loaded on every request
- N+1 queries in list endpoints (use eager loading)
- RabbitMQ queue configuration (ensure proper connection pooling)
- Media library file operations (consider async processing)
- Large Excel exports (consider chunking/queuing)

### Optimization Opportunities
- Cache tenant settings/configurations
- Index frequently queried columns
- Use query scoping instead of loading full collections
- Consider Redis caching for reference data (countries, currencies)

## 6. Infrastructure / DevOps

### RoadRunner Setup
- `spiral/roadrunner-cli` + `spiral/roadrunner-http`
- Ensure RoadRunner binary is .gitignored (58MB rr.exe was in repo)

### Queue Configuration
- RabbitMQ as primary queue driver
- Monitor dead letter queues
- Ensure queue retry logic is configured

### Monitoring
- Sentry for error tracking
- Telescope for local debugging (disabled in production)

## 7. Roadmap / Priority Tasks

### P0 — Critical
- [ ] Enable and test tenant DB bootstrapper
- [ ] Audit all repository queries for tenant scoping
- [ ] Add module.json for ProdactInfo
- [ ] Fix `rr.exe` in repo root (add to .gitignore, remove from tracking)

### P1 — Important
- [ ] Standardize nested module namespace conventions
- [ ] Extract helpers.php into organized service classes (incremental)
- [ ] Add integration tests for critical flows (auth, tenant isolation, orders)
- [ ] Document all API endpoints (OpenAPI/Swagger)

### P2 — Nice to Have
- [ ] Generic CRUD presenter base class
- [ ] Caching layer for reference data
- [ ] API versioning strategy
- [ ] Performance profiling for list endpoints
- [ ] CI/CD pipeline configuration

## 8. Ecommerce Module Specific Notes

The Ecommerce module is the most complex sub-system with 29 sub-modules. Key areas needing attention:
- Order workflow and payment integration
- Coupon/discount calculation accuracy
- Inventory management (Warehous)
- Multi-currency support (EcoCurrency)
- Client complaint handling flow
- Shop management and verification
