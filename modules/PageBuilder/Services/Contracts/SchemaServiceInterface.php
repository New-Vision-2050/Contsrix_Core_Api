<?php

namespace Modules\PageBuilder\Services\Contracts;

use Modules\PageBuilder\DTO\TableSchemaDTO;

interface SchemaServiceInterface
{
    /**
     * Get all available tables
     *
     * @return array
     */
    public function getTables(): array;

    /**
     * Get schema information for a specific table
     *
     * @param string $tableName
     * @return TableSchemaDTO
     * @throws \InvalidArgumentException
     */
    public function getTableSchema(string $tableName): TableSchemaDTO;

    /**
     * Check if a table exists
     *
     * @param string $tableName
     * @return bool
     */
    public function tableExists(string $tableName): bool;
}
