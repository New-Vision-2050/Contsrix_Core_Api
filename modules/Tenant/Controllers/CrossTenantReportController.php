<?php

namespace Modules\Tenant\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Tenant\Facades\Tenant;

class CrossTenantReportController extends Controller
{
    /**
     * Display a listing of all tenants.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tenants()
    {
        $tenants = Company::where('is_tenant', true)
            ->select('id', 'name', 'email', 'subdomain', 'tenant_created_at', 'tenant_plan')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tenants
        ]);
    }

    /**
     * Display a summary of all tenants.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tenantsSummary()
    {
        $tenants = Company::where('is_tenant', true)->get();
        $summary = [];

        foreach ($tenants as $tenant) {
            // Save the current tenant
            $currentTenant = Tenant::getTenant();
            
            // Set the tenant context
            Tenant::setTenant($tenant);
            
            // Get tenant-specific data
            $projectCount = DB::table('projects')->count();
            $taskCount = DB::table('tasks')->count();
            $documentCount = DB::table('documents')->count();
            
            // Add to summary
            $summary[] = [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'tenant_plan' => $tenant->tenant_plan,
                'project_count' => $projectCount,
                'task_count' => $taskCount,
                'document_count' => $documentCount,
                'created_at' => $tenant->tenant_created_at,
            ];
            
            // Reset to the original tenant
            if ($currentTenant) {
                Tenant::setTenant($currentTenant);
            } else {
                Tenant::resetTenant();
            }
        }

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Display users across all tenants.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function usersAcrossTenants(Request $request)
    {
        $query = CompanyUser::query()
            ->select('company_users.id', 'company_users.name', 'company_users.email', 'company_users.phone')
            ->selectRaw('COUNT(DISTINCT company_users_companies.company_id) as tenant_count')
            ->join('company_users_companies', 'company_users.id', '=', 'company_users_companies.company_user_id')
            ->join('companies', 'company_users_companies.company_id', '=', 'companies.id')
            ->where('companies.is_tenant', true)
            ->groupBy('company_users.id', 'company_users.name', 'company_users.email', 'company_users.phone');

        // Apply filters if provided
        if ($request->has('role')) {
            $query->where('company_users_companies.role', $request->role);
        }

        if ($request->has('status')) {
            $query->where('company_users_companies.status', $request->status);
        }

        $users = $query->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Display tenant activity report.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tenantActivity(Request $request)
    {
        $tenants = Company::where('is_tenant', true);
        
        // Apply filters if provided
        if ($request->has('plan')) {
            $tenants->where('tenant_plan', $request->plan);
        }
        
        if ($request->has('from_date')) {
            $tenants->where('tenant_created_at', '>=', $request->from_date);
        }
        
        if ($request->has('to_date')) {
            $tenants->where('tenant_created_at', '<=', $request->to_date);
        }
        
        $tenants = $tenants->get();
        $activityReport = [];

        foreach ($tenants as $tenant) {
            // Save the current tenant
            $currentTenant = Tenant::getTenant();
            
            // Set the tenant context
            Tenant::setTenant($tenant);
            
            // Get recent activity
            $recentProjects = DB::table('projects')
                ->select('id', 'name', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
                
            $recentTasks = DB::table('tasks')
                ->select('id', 'name', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
                
            $recentDocuments = DB::table('documents')
                ->select('id', 'name', 'file_type', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            // Add to activity report
            $activityReport[] = [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'recent_projects' => $recentProjects,
                'recent_tasks' => $recentTasks,
                'recent_documents' => $recentDocuments,
            ];
            
            // Reset to the original tenant
            if ($currentTenant) {
                Tenant::setTenant($currentTenant);
            } else {
                Tenant::resetTenant();
            }
        }

        return response()->json([
            'success' => true,
            'data' => $activityReport
        ]);
    }

    /**
     * Display user activity across tenants.
     *
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function userActivity($userId)
    {
        $user = CompanyUser::findOrFail($userId);
        $userTenants = $user->companies()->where('is_tenant', true)->get();
        $userActivity = [];

        foreach ($userTenants as $tenant) {
            // Save the current tenant
            $currentTenant = Tenant::getTenant();
            
            // Set the tenant context
            Tenant::setTenant($tenant);
            
            // Get user activity in this tenant
            $projects = DB::table('projects')
                ->where('company_user_id', $userId)
                ->select('id', 'name', 'status', 'created_at')
                ->get();
                
            $assignedTasks = DB::table('tasks')
                ->where('assigned_to', $userId)
                ->select('id', 'name', 'status', 'created_at')
                ->get();
                
            $documents = DB::table('documents')
                ->where('uploaded_by', $userId)
                ->select('id', 'name', 'file_type', 'created_at')
                ->get();
            
            // Add to user activity
            $userActivity[] = [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'role' => $tenant->pivot->role,
                'status' => $tenant->pivot->status,
                'projects' => $projects,
                'assigned_tasks' => $assignedTasks,
                'documents' => $documents,
            ];
            
            // Reset to the original tenant
            if ($currentTenant) {
                Tenant::setTenant($currentTenant);
            } else {
                Tenant::resetTenant();
            }
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'activity' => $userActivity
        ]);
    }
}