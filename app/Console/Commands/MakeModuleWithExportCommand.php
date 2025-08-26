<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class MakeModuleWithExportCommand extends Command
{
    protected $signature = 'make:module-with-export {name : The name of the module}';
    protected $description = 'Create a new module with automatic export functionality included';
    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $studlyName = Str::studly($name);
        $snakeName = Str::snake($name);
        $lowerName = Str::lower($name);
        
        $basePath = base_path("modules/{$studlyName}");

        $this->info("Creating module: {$studlyName} with export functionality");

        // Create directories
        $this->createDirectories($basePath);
        
        // Generate files
        $this->generateExportClass($basePath, $studlyName);
        $this->generateExportRequest($basePath, $studlyName);
        $this->generateController($basePath, $studlyName);
        $this->generateRoutes($basePath, $studlyName);

        $this->info("✅ Module {$studlyName} created successfully with export functionality!");
        $this->info("📁 Location: modules/{$studlyName}");
        $this->info("🚀 Export functionality included:");
        $this->info("   • Export class: {$studlyName}Export");
        $this->info("   • Export request: Export{$studlyName}Request");
        $this->info("   • Controller with export method");
        $this->info("   • Routes with /export endpoint");

        return 0;
    }

    protected function generateModuleStructure(string $name, string $modulePath, array $fields, array $relationships): void
    {
        $studlyName = Str::studly($name);
        $snakeName = Str::snake($name);
        $lowerName = Str::lower($name);
        $pluralStudly = Str::plural($studlyName);
        
        $basePath = base_path("{$modulePath}/{$studlyName}");

        // Create directory structure
        $this->createDirectories($basePath);

        // Generate files with export functionality
        $this->generateExportClass($basePath, $studlyName, $fields);
        $this->generateExportRequest($basePath, $studlyName, $fields);
        $this->generateRepository($basePath, $studlyName, $relationships);
        $this->generateService($basePath, $studlyName);
        $this->generateController($basePath, $studlyName);
        $this->generateRoutes($basePath, $studlyName);
        
        $this->info("✓ Generated all files with export functionality");
    }

    protected function createDirectories(string $basePath): void
    {
        $directories = [
            'Controllers',
            'Exports', 
            'Requests',
            'Services',
            'Repositories',
            'Resources/routes'
        ];

        foreach ($directories as $dir) {
            $this->files->makeDirectory("{$basePath}/{$dir}", 0755, true);
        }
    }

    protected function generateExportClass(string $basePath, string $studlyName, array $fields): void
    {
        $stub = $this->files->get(base_path('stubs/modules/exports/export.stub'));
        
        $headings = $this->generateHeadings($fields);
        $mapping = $this->generateMapping($fields);
        $filterableColumns = $this->generateFilterableColumns($fields);

        $content = str_replace([
            '$NAMESPACE$',
            '$CLASS$',
            '$HEADINGS$',
            '$MAPPING$',
            '$FILTERABLE_COLUMNS$'
        ], [
            "Modules\\{$studlyName}\\Exports",
            "{$studlyName}Export",
            $headings,
            $mapping,
            $filterableColumns
        ], $stub);

        $this->files->put("{$basePath}/Exports/{$studlyName}Export.php", $content);
    }

    protected function generateExportRequest(string $basePath, string $studlyName, array $fields): void
    {
        $stub = $this->files->get(base_path('stubs/modules/exports/export-request.stub'));
        
        $specificRules = $this->generateSpecificRules($fields);
        $specificFilters = $this->generateSpecificFilters($fields);

        $content = str_replace([
            '$NAMESPACE$',
            '$CLASS$',
            '$SPECIFIC_RULES$',
            '$SPECIFIC_FILTERS$'
        ], [
            "Modules\\{$studlyName}\\Requests",
            "Export{$studlyName}Request",
            $specificRules,
            $specificFilters
        ], $stub);

        $this->files->put("{$basePath}/Requests/Export{$studlyName}Request.php", $content);
    }

    protected function generateRepository(string $basePath, string $studlyName, array $relationships): void
    {
        $stub = $this->files->get(base_path('stubs/modules/exports/repository-with-export.stub'));
        
        $exportRelationships = !empty($relationships) ? "'" . implode("',\n            '", $relationships) . "'" : '';

        $content = str_replace([
            '$NAMESPACE$',
            '$CLASS$',
            '$MODEL$',
            '$MODEL_NAMESPACE$',
            '$STUDLY_NAME$',
            '$EXPORT_RELATIONSHIPS$'
        ], [
            "Modules\\{$studlyName}\\Repositories",
            "{$studlyName}Repository",
            $studlyName,
            "Modules\\{$studlyName}\\Models\\{$studlyName}",
            $studlyName,
            $exportRelationships
        ], $stub);

        $this->files->put("{$basePath}/Repositories/{$studlyName}Repository.php", $content);
    }

    protected function generateService(string $basePath, string $studlyName): void
    {
        $stub = $this->files->get(base_path('stubs/modules/exports/service-with-export.stub'));
        
        $content = str_replace([
            '$NAMESPACE$',
            '$CLASS$',
            '$DTO_NAMESPACE$',
            '$MODEL_NAMESPACE$',
            '$REPOSITORY_NAMESPACE$',
            '$MODEL$',
            '$REPOSITORY_CLASS$',
            '$CREATE_DTO_CLASS$',
            '$STUDLY_NAME$'
        ], [
            "Modules\\{$studlyName}\\Services",
            "{$studlyName}CRUDService",
            "Modules\\{$studlyName}\\DTO\\Create{$studlyName}DTO",
            "Modules\\{$studlyName}\\Models\\{$studlyName}",
            "Modules\\{$studlyName}\\Repositories\\{$studlyName}Repository",
            $studlyName,
            "{$studlyName}Repository",
            "Create{$studlyName}DTO",
            $studlyName
        ], $stub);

        $this->files->put("{$basePath}/Services/{$studlyName}CRUDService.php", $content);
    }

    protected function generateController(string $basePath, string $studlyName): void
    {
        $stub = $this->files->get(base_path('stubs/modules/exports/controller-with-export.stub'));
        
        // This would be a complex replacement with many placeholders
        // For brevity, I'll show the key export-related replacements
        $content = str_replace([
            '$NAMESPACE$',
            '$CLASS$',
            '$EXPORT_NAMESPACE$',
            '$REQUEST_EXPORT_NAMESPACE$',
            '$EXPORT_CLASS$',
            '$EXPORT_REQUEST_CLASS$',
            '$SNAKE_NAME$',
            '$LOWER_NAME$'
        ], [
            "Modules\\{$studlyName}\\Controllers",
            "{$studlyName}Controller",
            "Modules\\{$studlyName}\\Exports\\{$studlyName}Export",
            "Modules\\{$studlyName}\\Requests\\Export{$studlyName}Request",
            "{$studlyName}Export",
            "Export{$studlyName}Request",
            Str::snake($studlyName),
            Str::lower($studlyName)
        ], $stub);

        $this->files->put("{$basePath}/Controllers/{$studlyName}Controller.php", $content);
    }

    protected function generateRoutes(string $basePath, string $studlyName): void
    {
        $stub = $this->files->get(base_path('stubs/modules/exports/routes-with-export.stub'));
        
        $content = str_replace([
            '$CONTROLLER_NAMESPACE$',
            '$CONTROLLER_CLASS$',
            '$TENANCY_MIDDLEWARE$'
        ], [
            "Modules\\{$studlyName}\\Controllers\\{$studlyName}Controller",
            "{$studlyName}Controller",
            "\\Stancl\\Tenancy\\Middleware\\InitializeTenancyByRequestData::class"
        ], $stub);

        $this->files->put("{$basePath}/Resources/routes/api.php", $content);
    }

    protected function parseFields(string $fieldsString): array
    {
        if (empty($fieldsString)) {
            return ['name' => 'string'];
        }

        $fields = [];
        foreach (explode(',', $fieldsString) as $field) {
            [$name, $type] = explode(':', trim($field));
            $fields[trim($name)] = trim($type);
        }

        return $fields;
    }

    protected function parseRelationships(string $relationshipsString): array
    {
        if (empty($relationshipsString)) {
            return [];
        }

        return array_map('trim', explode(',', $relationshipsString));
    }

    protected function generateHeadings(array $fields): string
    {
        $headings = [];
        foreach (array_keys($fields) as $field) {
            $headings[] = "'" . Str::title(str_replace('_', ' ', $field)) . "'";
        }
        
        return implode(",\n            ", $headings);
    }

    protected function generateMapping(array $fields): string
    {
        $mappings = [];
        foreach (array_keys($fields) as $field) {
            $mappings[] = "\$row->{$field}";
        }
        
        return implode(",\n            ", $mappings);
    }

    protected function generateFilterableColumns(array $fields): string
    {
        $columns = [];
        foreach (array_keys($fields) as $field) {
            $columns[] = "'{$field}'";
        }
        
        return implode(",\n            ", $columns);
    }

    protected function generateSpecificRules(array $fields): string
    {
        $rules = [];
        foreach ($fields as $field => $type) {
            if ($type === 'string') {
                $rules[] = "'{$field}' => 'sometimes|string|max:255'";
            } elseif ($type === 'integer') {
                $rules[] = "'{$field}' => 'sometimes|integer'";
            } elseif ($type === 'date') {
                $rules[] = "'{$field}' => 'sometimes|date'";
            }
        }
        
        return implode(",\n            ", $rules);
    }

    protected function generateSpecificFilters(array $fields): string
    {
        $filters = [];
        foreach (array_keys($fields) as $field) {
            $filters[] = "if (\$this->has('{$field}')) {\n            \$filters['{$field}'] = \$this->get('{$field}');\n        }";
        }
        
        return implode("\n\n        ", $filters);
    }
}
