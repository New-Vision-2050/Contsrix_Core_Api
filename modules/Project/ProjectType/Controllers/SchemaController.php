<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectType\Models\Schema;
use Modules\Project\ProjectType\Presenters\SchemaPresenter;

class SchemaController extends Controller
{
    /**
     * Get all project schemas
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Schema::query();

            // Optional filtering by name
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Pagination
            $perPage = (int) $request->get('per_page', 50);
            $page = (int) $request->get('page', 1);

            if ($perPage > 0) {
                $schemas = $query->paginate($perPage, ['*'], 'page', $page);
                
                $data = $schemas->items();
                $pagination = [
                    'total' => $schemas->total(),
                    'per_page' => $schemas->perPage(),
                    'current_page' => $schemas->currentPage(),
                    'last_page' => $schemas->lastPage(),
                ];

                return Json::items(
                    SchemaPresenter::collection($data),
                    paginationSettings: $pagination
                );
            }

            // Get all without pagination
            $schemas = $query->get();
            return Json::items(SchemaPresenter::collection($schemas->toArray()));
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Get a single schema by ID
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $schemaId = $request->route('id');
            
            $schema = Schema::find($schemaId);

            if (!$schema) {
                return Json::error('Schema not found', 404);
            }

            $presenter = new SchemaPresenter($schema);
            return Json::item($presenter->getData());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }
}
