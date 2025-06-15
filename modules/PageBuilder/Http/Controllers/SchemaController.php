<?php

namespace Modules\PageBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PageBuilder\Services\Contracts\SchemaServiceInterface;

class SchemaController extends Controller
{
    public function __construct(
        private readonly SchemaServiceInterface $schemaService
    ) {}

    /**
     * Get all available tables
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $tables = $this->schemaService->getTables();
            return response()->json($tables);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get schema for a specific table
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $tableName = $request->input('tableName');
            
            if (!$tableName) {
                return response()->json(['error' => 'Table name is required'], 400);
            }

            $schema = $this->schemaService->getTableSchema($tableName);
            return response()->json($schema->toArray());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
