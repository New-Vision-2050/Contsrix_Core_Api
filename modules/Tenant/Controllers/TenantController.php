<?php

namespace Modules\Tenant\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Tenant\Facades\Tenant;

class TenantController extends Controller
{
    /**
     * Display the current tenant information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function current()
    {
        $tenant = Tenant::getTenant();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No tenant context set'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'subdomain' => $tenant->subdomain,
                'plan' => $tenant->tenant_plan,
                'created_at' => $tenant->tenant_created_at,
                'expires_at' => $tenant->tenant_expires_at,
            ]
        ]);
    }

    /**
     * Create a new tenant.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies,email',
            'phone' => 'required|string|unique:companies,phone',
            'subdomain' => 'required|string|unique:companies,subdomain|alpha_dash',
            'country_id' => 'required|string|exists:countries,id',
            'company_type_id' => 'required|string|exists:company_types,id',
            'company_field_id' => 'required|string|exists:company_fields,id',
            'registration_type_id' => 'required|string|exists:company_registration_types,id',
            'plan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create the tenant
            $tenant = new Company();
            $tenant->name = $request->name;
            $tenant->email = $request->email;
            $tenant->phone = $request->phone;
            $tenant->subdomain = $request->subdomain;
            $tenant->country_id = $request->country_id;
            $tenant->company_type_id = $request->company_type_id;
            $tenant->company_field_id = $request->company_field_id;
            $tenant->registration_type_id = $request->registration_type_id;
            $tenant->is_tenant = true;
            $tenant->tenant_created_at = now();
            $tenant->tenant_plan = $request->plan ?? 'basic';
            $tenant->save();

            // Create the schema for the tenant
            $tenantManager = app('tenant.manager');
            $schemaCreated = $tenantManager->createTenantSchema($tenant);

            if (!$schemaCreated) {
                throw new \Exception('Failed to create schema for tenant');
            }

            // Run migrations for the tenant
            $tenantManager->migrateTenant($tenant);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tenant created successfully',
                'data' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'email' => $tenant->email,
                    'subdomain' => $tenant->subdomain,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tenant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the tenant information.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $tenant = Tenant::getTenant();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No tenant context set'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:companies,email,' . $tenant->id,
            'phone' => 'nullable|string|unique:companies,phone,' . $tenant->id,
            'country_id' => 'nullable|string|exists:countries,id',
            'company_type_id' => 'nullable|string|exists:company_types,id',
            'company_field_id' => 'nullable|string|exists:company_fields,id',
            'registration_type_id' => 'nullable|string|exists:company_registration_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update the tenant
            if ($request->has('name')) {
                $tenant->name = $request->name;
            }
            
            if ($request->has('email')) {
                $tenant->email = $request->email;
            }
            
            if ($request->has('phone')) {
                $tenant->phone = $request->phone;
            }
            
            if ($request->has('country_id')) {
                $tenant->country_id = $request->country_id;
            }
            
            if ($request->has('company_type_id')) {
                $tenant->company_type_id = $request->company_type_id;
            }
            
            if ($request->has('company_field_id')) {
                $tenant->company_field_id = $request->company_field_id;
            }
            
            if ($request->has('registration_type_id')) {
                $tenant->registration_type_id = $request->registration_type_id;
            }
            
            $tenant->save();

            return response()->json([
                'success' => true,
                'message' => 'Tenant updated successfully',
                'data' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'email' => $tenant->email,
                    'phone' => $tenant->phone,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tenant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tenant statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $tenant = Tenant::getTenant();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No tenant context set'
            ], 400);
        }

        try {
            // Get statistics for the current tenant
            $projectCount = DB::table('projects')->count();
            $taskCount = DB::table('tasks')->count();
            $documentCount = DB::table('documents')->count();
            
            $completedTaskCount = DB::table('tasks')->where('status', 'completed')->count();
            $pendingTaskCount = DB::table('tasks')->where('status', 'pending')->count();
            
            $recentProjects = DB::table('projects')
                ->select('id', 'name', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'project_count' => $projectCount,
                    'task_count' => $taskCount,
                    'document_count' => $documentCount,
                    'completed_task_count' => $completedTaskCount,
                    'pending_task_count' => $pendingTaskCount,
                    'recent_projects' => $recentProjects,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get tenant statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}