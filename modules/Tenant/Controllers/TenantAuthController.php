<?php

declare(strict_types=1);

namespace Modules\Tenant\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenant\Services\TenantAuthService;

class TenantAuthController extends Controller
{
    /**
     * @var TenantAuthService
     */
    private $tenantAuthService;

    /**
     * TenantAuthController constructor.
     *
     * @param TenantAuthService $tenantAuthService
     */
    public function __construct(TenantAuthService $tenantAuthService)
    {
        $this->tenantAuthService = $tenantAuthService;
    }

    /**
     * Login a tenant user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->tenantAuthService->login(
            $request->input('email'),
            $request->input('password')
        );

        if (!$result) {
            return Json::error(__('auth.failed'), 401);
        }

        return Json::success([
            'token' => $result['token'],
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
                'tenant_id' => $result['tenant_id'],
                'company_id' => $result['company_id'],
                'role' => $result['role'],
            ],
        ]);
    }

    /**
     * Get the authenticated user.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        $user = $this->tenantAuthService->getAuthenticatedUser();

        if (!$user) {
            return Json::error(__('auth.unauthenticated'), 401);
        }

        // Get the user's role for this company
        $role = 'user';
        $companyRelation = $user->companies()->where('company_id', tenant()->company_id)->first();
        if ($companyRelation) {
            $role = $companyRelation->pivot->role ?? 'user';
        }

        return Json::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant_id' => tenant()->id,
                'company_id' => tenant()->company_id,
                'role' => $role,
            ],
        ]);
    }

    /**
     * Refresh the token.
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        $token = $this->tenantAuthService->refreshToken();

        if (!$token) {
            return Json::error(__('auth.token.invalid'), 401);
        }

        return Json::success([
            'token' => $token,
        ]);
    }

    /**
     * Logout the user.
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        $success = $this->tenantAuthService->logout();

        if (!$success) {
            return Json::error(__('auth.token.invalid'), 401);
        }

        return Json::success([
            'message' => __('auth.logged_out'),
        ]);
    }
}
