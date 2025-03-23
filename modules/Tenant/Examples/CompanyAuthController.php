<?php

declare(strict_types=1);

namespace Modules\Tenant\Examples;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Presenters\Json;

/**
 * Example of a company authentication controller.
 * This is an alternative approach to creating a separate TenantAuthController.
 */
class CompanyAuthController extends Controller
{
    /**
     * @var CompanyAuthService
     */
    protected $authService;

    /**
     * Create a new controller instance.
     *
     * @param CompanyAuthService $authService
     */
    public function __construct(CompanyAuthService $authService)
    {
        $this->authService = $authService;
        $this->middleware('company.auth', ['except' => ['login']]);
    }

    /**
     * Login a company user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password')
        );

        if (!$result) {
            return Json::error('Invalid credentials', 401);
        }

        return Json::success([
            'token' => $result['token'],
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
                'company_id' => $result['company_id'],
                'role' => $result['role'],
            ],
        ]);
    }

    /**
     * Get the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = $this->authService->getAuthenticatedUser();

        if (!$user) {
            return Json::error('Unauthenticated', 401);
        }

        // Get the user's role for this company
        $role = 'user';
        $companyRelation = $user->companies()->where('company_id', tenant()->id)->first();
        if ($companyRelation) {
            $role = $companyRelation->pivot->role ?? 'user';
        }

        return Json::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => tenant()->id,
                'role' => $role,
            ],
        ]);
    }

    /**
     * Refresh the JWT token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $token = $this->authService->refreshToken();

        if (!$token) {
            return Json::error('Could not refresh token', 401);
        }

        return Json::success([
            'token' => $token,
        ]);
    }

    /**
     * Logout the user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->authService->logout();

        return Json::success([
            'message' => 'Successfully logged out',
        ]);
    }
}