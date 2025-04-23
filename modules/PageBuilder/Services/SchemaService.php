<?php

namespace Modules\PageBuilder\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Modules\PageBuilder\Services\Contracts\SchemaServiceInterface;
use Modules\PageBuilder\DTO\TableSchemaDTO;
use Modules\PageBuilder\DTO\ColumnDTO;
use Modules\PageBuilder\DTO\ForeignKeyDTO;
use Modules\PageBuilder\DTO\RelationshipDTO;

class SchemaService implements SchemaServiceInterface
{
    public function getTables(): array
    {
        $cacheKey = 'page-builder:tables';
        $cacheTtl = Config::get('page-builder.cache.ttl', 3600);

        if (false && Config::get('page-builder.cache.enabled', true)) {
            return Cache::remember($cacheKey, $cacheTtl, function () {
                return $this->fetchTables();
            });
        }

        return $this->fetchTables();
    }

    private function fetchTables(): array
    {
        $excludedTables = Config::get('page-builder.excluded_tables', []);
        $tables = DB::select('SHOW TABLES');
        return collect($tables)
            ->filter(fn ($table) => !in_array($table->{"Tables_in_".env('DB_DATABASE')}, $excludedTables))
            ->map(fn ($table) => $table->{"Tables_in_".env('DB_DATABASE')})
            ->values()
            ->toArray();
    }

    public function getTableSchema(string $tableName): TableSchemaDTO
    {
        if (!$this->tableExists($tableName)) {
            throw new \InvalidArgumentException("Table {$tableName} does not exist");
        }

        $cacheKey = "page-builder:schema:{$tableName}";
        $cacheTtl = Config::get('page-builder.cache.ttl', 3600);

        if (Config::get('page-builder.cache.enabled', true)) {
            return Cache::remember($cacheKey, $cacheTtl, function () use ($tableName) {
                return $this->fetchTableSchema($tableName);
            });
        }

        return $this->fetchTableSchema($tableName);
    }

    private function fetchTableSchema(string $tableName): TableSchemaDTO
    {
        $columns = $this->getTableColumns($tableName);
        $relationships = $this->getTableRelationships($tableName);

        return new TableSchemaDTO(
            name: $tableName,
            columns: $columns,
            relationships: $relationships
        );
    }

    public function tableExists(string $tableName): bool
    {
        return Schema::hasTable($tableName);
    }

    public function getTableRelationships(string $tableName): array
    {
        $relationships = [];

        // Get foreign key constraints
        $foreignKeys = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys($tableName);

        foreach ($foreignKeys as $foreignKey) {
            $relationships[] = new RelationshipDTO(
                type: 'belongsTo',
                model: $this->guessModelFromTable($foreignKey->getForeignTableName()),
                foreignKey: $foreignKey->getLocalColumns()[0],
                localKey: $foreignKey->getForeignColumns()[0]
            );
        }

        // Check for inverse relationships (hasMany/hasOne)
        foreach ($this->getTables() as $otherTable) {
            $otherForeignKeys = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableForeignKeys($otherTable);

            foreach ($otherForeignKeys as $foreignKey) {
                if ($foreignKey->getForeignTableName() === $tableName) {
                    $relationships[] = new RelationshipDTO(
                        type: 'hasMany',
                        model: $this->guessModelFromTable($otherTable),
                        foreignKey: $foreignKey->getLocalColumns()[0],
                        localKey: $foreignKey->getForeignColumns()[0]
                    );
                }
            }
        }

        return $relationships;
    }

    private function getTableColumns(string $tableName): array
    {
        $columns = [];
        $columnInfo = DB::select("SHOW FULL COLUMNS FROM {$tableName}");

        foreach ($columnInfo as $column) {
            $foreignKey = $this->getColumnForeignKey($tableName, $column->Field);

            $columns[] = new ColumnDTO(
                name: $column->Field,
                type: $this->parseColumnType($column->Type),
                required: $column->Null === 'NO',
                nullable: $column->Null === 'YES',
                default: $column->Default,
                foreignKey: $foreignKey
            );
        }

        return $columns;
    }

    private function getColumnForeignKey(string $tableName, string $columnName): ?ForeignKeyDTO
    {
        $foreignKeys = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys($tableName);

        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey->getLocalColumns()[0] === $columnName) {
                return new ForeignKeyDTO(
                    references: $foreignKey->getForeignColumns()[0],
                    on: $foreignKey->getForeignTableName()
                );
            }
        }

        return null;
    }

    private function parseColumnType(string $type): string
    {
        // Extract base type without length/precision
        preg_match('/^([a-z]+)/', $type, $matches);
        return $matches[1] ?? 'string';
    }

    private function guessModelFromTable(string $table): string
    {
        return ucfirst(str_singular($table));
    }
}
