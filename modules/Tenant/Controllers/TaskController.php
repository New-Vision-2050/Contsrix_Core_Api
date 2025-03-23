<?php

namespace Modules\Tenant\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Models\Task;
use Modules\Tenant\Models\Project;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Task::query();

        // Apply filters if provided
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        // Apply sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $tasks = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|string|exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|string|exists:company_users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verify the project exists in the current tenant
            $project = Project::findOrFail($request->project_id);

            $task = new Task();
            $task->project_id = $request->project_id;
            $task->name = $request->name;
            $task->description = $request->description;
            $task->due_date = $request->due_date;
            $task->status = $request->status ?? 'pending';
            $task->assigned_to = $request->assigned_to;
            $task->save();

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => $task
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $task = Task::with(['project', 'assignee'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'nullable|string|exists:projects,id',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|string|exists:company_users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::findOrFail($id);
            
            // Update task fields if provided
            if ($request->has('project_id')) {
                // Verify the project exists in the current tenant
                $project = Project::findOrFail($request->project_id);
                $task->project_id = $request->project_id;
            }
            
            if ($request->has('name')) {
                $task->name = $request->name;
            }
            
            if ($request->has('description')) {
                $task->description = $request->description;
            }
            
            if ($request->has('due_date')) {
                $task->due_date = $request->due_date;
            }
            
            if ($request->has('status')) {
                $task->status = $request->status;
            }
            
            if ($request->has('assigned_to')) {
                $task->assigned_to = $request->assigned_to;
            }
            
            $task->save();

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'data' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $task = Task::findOrFail($id);
            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}