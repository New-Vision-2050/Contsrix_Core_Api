<?php

namespace Modules\PageBuilder\Services\Contracts;

use Modules\PageBuilder\DTO\TableSchemaDTO;

interface SchemaServiceInterface
{
    /**
     * Get list of all available tables
     *
     * @return array<string>
     */
    public function getTables(): array;

    /**
     * Get schema information for a specific table
     *
     * @param string $tableName
     * @return TableSchemaDTO
     */
    public function getTableSchema(string $tableName): TableSchemaDTO;

    /**
     * Check if table exists
     *
     * @param string $tableName
     * @return bool
     */
    public function tableExists(string $tableName): bool;

    /**
     * Get foreign key relationships for a table
     *
     * @param string $tableName
     * @return array
     */
    public function getTableRelationships(string $tableName): array;
}