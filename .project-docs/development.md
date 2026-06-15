# Contsrix_Core_Api — Development Guide

## 1. Environment Setup

### Requirements
- PHP 8.2+
- MySQL/PostgreSQL
- RabbitMQ (for queues)
- RoadRunner (for Octane)

### Quick Start
```bash
# Clone & install
git clone <repo-url>
cd Contsrix_Core_Api
composer install
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --database=central
php artisan db:seed

# RoadRunner (development)
php artisan octane:start --server=roadrunner

# Queue worker
php artisan queue:work rabbitmq
```

### Key .env Variables
```
APP_URL=https://core-be-stage.constrix-nv.com
DB_CONNECTION=central
AUTH_GUARD=api
AUTH_MODEL=Modules\User\Models\User
```

## 2. Module Anatomy (Standard Pattern)

Every module follows this structure:

```
modules/ModuleName/
├── module.json                      # REQUIRED: name, alias, providers
├── Commands/                        # Optional: Command DTOs
├── Controllers/                     # HTTP request handlers
├── DTO/                             # Data Transfer Objects (constructor promotion)
├── Database/
│   ├── factories/                   # Model factories
│   └── Migrations/                  # Migration files
├── Exports/                         # Excel exports (optional)
├── Filters/                         # Query filter classes (optional)
├── Handlers/                        # Complex operation handlers
├── Models/                          # Eloquent models
├── Presenters/                      # API response transformers
├── Providers/                       # ServiceProvider (extends ModuleServiceProvider)
├── Repositories/                    # Data access layer (extends BaseRepository)
├── Requests/                        # FormRequest validation classes
├── Resources/
│   └── routes/
│       └── api.php                  # API route definitions
└── Services/                        # Business logic
```

## 3. Module Creation Template

### Step 1: module.json
```json
{
    "name": "NewModule",
    "alias": "newmodule",
    "description": "Description of the module",
    "keywords": [],
    "priority": 0,
    "providers": [
        "Modules\\NewModule\\Providers\\NewModuleServiceProvider"
    ],
    "aliases": {},
    "files": [],
    "requires": [],
    "namespace": "Modules"
}
```

### Step 2: ServiceProvider
```php
<?php
namespace Modules\NewModule\Providers;

use BasePackage\Shared\Module\ModuleServiceProvider;

class NewModuleServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'NewModule';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerMigrations();
        $this->registerCommands();
        $this->registerSchedules();
        $this->registerEventListeners();
    }
}
```

### Step 3: Model
```php
<?php
namespace Modules\NewModule\Models;

use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewEntity extends Model
{
    use UuidTrait;
    use BaseFilterable;
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'status'];
    protected $casts = ['status' => 'boolean'];
}
```

### Step 4: DTO
```php
<?php
namespace Modules\NewModule\DTO;

class CreateNewEntityDTO
{
    public function __construct(
        public string $name,
        public string $description,
        public bool $status = true,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
```

### Step 5: Repository (extends BaseRepository)
```php
<?php
namespace Modules\NewModule\Repositories;

use Modules\NewModule\Models\NewEntity;
use BasePackage\Shared\Repositories\BaseRepository;

class NewEntityRepository extends BaseRepository
{
    public function __construct(NewEntity $model)
    {
        parent::__construct($model);
    }
}
```

### Step 6: FormRequest
```php
<?php
namespace Modules\NewModule\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\NewModule\DTO\CreateNewEntityDTO;

class CreateNewEntityRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ];
    }

    public function toDto(): CreateNewEntityDTO
    {
        return new CreateNewEntityDTO(
            name: $this->input('name'),
            description: $this->input('description', ''),
            status: $this->boolean('status', true),
        );
    }
}
```

### Step 7: Service
```php
<?php
namespace Modules\NewModule\Services;

use Modules\NewModule\DTO\CreateNewEntityDTO;
use Modules\NewModule\Repositories\NewEntityRepository;
use Illuminate\Database\Eloquent\Collection;

class NewEntityService
{
    public function __construct(
        private NewEntityRepository $repository,
    ) {}

    public function list(int $page, int $perPage): Collection
    {
        return $this->repository->paginate($page, $perPage);
    }

    public function create(CreateNewEntityDTO $dto)
    {
        return $this->repository->create($dto->toArray());
    }

    public function find(string $id)
    {
        return $this->repository->findOneOrFail($id);
    }

    public function update(string $id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): void
    {
        $this->repository->delete($id);
    }
}
```

### Step 8: Presenter
```php
<?php
namespace Modules\NewModule\Presenters;

use Modules\NewModule\Models\NewEntity;

class NewEntityPresenter
{
    public static function present(NewEntity $entity): array
    {
        return [
            'id' => $entity->id,
            'name' => $entity->name,
            'description' => $entity->description,
            'status' => $entity->status,
            'created_at' => $entity->created_at,
        ];
    }

    public static function collection($entities): array
    {
        return $entities->map(fn($e) => self::present($e))->toArray();
    }
}
```

### Step 9: Controller
```php
<?php
namespace Modules\NewModule\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\NewModule\Requests\CreateNewEntityRequest;
use Modules\NewModule\Services\NewEntityService;
use Modules\NewModule\Presenters\NewEntityPresenter;

class NewEntityController extends Controller
{
    public function __construct(
        private NewEntityService $service,
    ) {}

    public function index(): JsonResponse
    {
        $entities = $this->service->list(request('page', 1), request('per_page', 10));
        return response()->json(NewEntityPresenter::collection($entities));
    }

    public function store(CreateNewEntityRequest $request): JsonResponse
    {
        $entity = $this->service->create($request->toDto());
        return response()->json(NewEntityPresenter::present($entity), 201);
    }

    public function show(string $id): JsonResponse
    {
        $entity = $this->service->find($id);
        return response()->json(NewEntityPresenter::present($entity));
    }

    public function update(string $id, CreateNewEntityRequest $request): JsonResponse
    {
        $entity = $this->service->update($id, $request->toDto()->toArray());
        return response()->json(NewEntityPresenter::present($entity));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }
}
```

### Step 10: Routes (Resources/routes/api.php)
```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\NewModule\Controllers\NewEntityController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/newmodule', [NewEntityController::class, 'index']);
    Route::post('/newmodule', [NewEntityController::class, 'store']);
    Route::get('/newmodule/{id}', [NewEntityController::class, 'show']);
    Route::put('/newmodule/{id}', [NewEntityController::class, 'update']);
    Route::delete('/newmodule/{id}', [NewEntityController::class, 'destroy']);
});
```

## 4. Coding Conventions

### Naming
- **Modules:** PascalCase (`User`, `CompanyUser`, `EcoProduct`)
- **Models:** PascalCase, singular (`User`, `Company`)
- **Tables:** snake_case, plural (`users`, `companies`)
- **DTOs:** `{Action}{Entity}DTO` (`CreateUserDTO`, `UpdateUserDTO`)
- **Requests:** `{Action}{Entity}Request` (`CreateUserRequest`, `GetUserListRequest`)
- **Services:** `{Entity}Service` or `{Entity}CRUDService` (`UserCRUDService`)
- **Repositories:** `{Entity}Repository` (`UserRepository`)
- **Presenters:** `{Entity}Presenter` (`UserPresenter`)
- **Controllers:** `{Entity}Controller` (`UserController`)
- **Routes:** kebab-case alias from module.json (`/user`, `/company-user`)

### Code Organization
- **Controllers:** Thin — validate, call service, present response. NO business logic.
- **Services:** Business logic. Orchestrate repositories, call other services.
- **Repositories:** Data access ONLY. Extend `BaseRepository`.
- **DTOs:** Use PHP 8.2 constructor promotion. Include `toArray()`.
- **Presenters:** Static methods. Format model → API response array.
- **Requests:** `authorize()` + `rules()` + `toDto()` method.
- **Handlers:** For complex multi-step operations. Use when Service gets too large.

### Tenancy
- Always include `InitializeTenancyByRequestData` middleware on tenant-scoped routes
- Repository queries should be tenant-aware (scoped to current tenant)
- Use `CustomBelongsToTenant` or `ForcedBelongsToTenant` traits where needed

### UUID & Primary Keys
- All models use UUIDs via `UuidTrait`
- `$incrementing = false`, `$keyType = 'string'` handled by trait

### Permissions
- Define permission enums per module: `modules/XXX/Enums/Permission.php`
- Use `->permission()` route macro for route-level checks
- Check permissions in FormRequests or Controllers

## 5. Key Traits & Helpers

### Traits (app/Traits/)
| Trait | Purpose |
|---|---|
| `CustomBelongsToTenant` | Manual tenant scoping on models |
| `ForcedBelongsToTenant` | Enforced tenant scoping |
| `HasExport` | Excel export support for models |
| `HasExportController` | Export endpoint for controllers |
| `HasExportService` | Export logic for services |
| `Shareable` | Resource sharing between tenants |
| `CalculateTreeManagementHierarchy` | Tree traversal for org hierarchy |

### BasePackage Traits
| Trait | Purpose |
|---|---|
| `UuidTrait` | UUID primary keys, auto-generation |
| `BaseFilterable` | Dynamic query filtering |
| `HasTranslations` | Multi-language field support |

### helpers.php (~3000 lines)
- Global utility functions used throughout the app
- Common in legacy Laravel projects — be aware of its contents when writing new code
- Check helpers.php before writing new utility functions

## 6. Nested Module Convention

For modules with sub-modules (Company, Ecommerce, Shared, etc.):

```
modules/ParentName/
├── SubModuleName/
│   ├── module.json          # namespace: "Modules\\ParentName"
│   ├── Controllers/
│   ├── Services/
│   └── ...
└── (no parent module.json)
```

The parent directory has NO module.json — each sub-module has its own. Routes are registered per sub-module.

## 7. Debugging & Tools

- **Telescope:** Available in non-production (`laravel/telescope`)
- **Tinker:** `php artisan tinker` for REPL
- **Logs:** standard Laravel logging
- **Sentry:** `sentry/sentry-laravel` for error tracking

## 8. Testing

- PHPUnit (`phpunit/phpunit` ^11.0)
- Model factories for test data (`Database/factories/`)
- Tests go in `/tests/` directory
- Some modules have dedicated `tests/` directories (e.g., CompanyCore/tests/)

## 9. Common Gotchas

1. **Tenant bootstrappers commented out** — DB tenancy is NOT automatic. Must manually scope queries.
2. **helpers.php is 3000 lines** — many global functions. Check before adding new ones.
3. **Some modules missing module.json** — `ProdactInfo` has no module.json (may not be auto-loaded).
4. **Nested namespaces** — `Company\CompanyCore` uses `namespace Modules` while `Company\CompanyField` uses `namespace Modules\Company`. Inconsistent.
5. **No centralized route file** — each module defines its own routes. No `routes/api.php` at root.
6. **Company model is the tenant** — configured in `config/tenancy.php`.
7. **58MB rr.exe** might exist in repo root — exclude from version control.
8. **OwenIt Auditing** — models must implement `Auditable` contract to be tracked.
