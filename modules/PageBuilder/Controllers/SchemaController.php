<?php

namespace Modules\PageBuilder\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\PageBuilder\Services\Contracts\SchemaServiceInterface;
use Symfony\Component\HttpFoundation\Response;

class SchemaController extends Controller
{
    public function __construct(
        private SchemaServiceInterface $schemaService
    ) {}

    /**
     * Get list of all available tables
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $tables = $this->schemaService->getTables();
            return response()->json($tables);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Failed to fetch tables: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get schema for a specific table
     *
     * @param string $tableName
     * @return JsonResponse
     */
    public function show(string $tableName): JsonResponse
    {
        try {
            if (!$this->schemaService->tableExists($tableName)) {
                return response()->json(
                    ['error' => 'Table not found'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $schema = $this->schemaService->getTableSchema($tableName);
            return response()->json($schema->toArray());
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Failed to fetch table schema: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}