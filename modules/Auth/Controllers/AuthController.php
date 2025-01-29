<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Handlers\DeleteAuthHandler;
use Modules\Auth\Handlers\UpdateAuthHandler;
use Modules\Auth\Presenters\AuthPresenter;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\DeleteAuthRequest;
use Modules\Auth\Requests\GetAuthListRequest;
use Modules\Auth\Requests\GetAuthRequest;
use Modules\Auth\Requests\LogoutRequest;
use Modules\Auth\Requests\UpdateAuthRequest;
use Modules\Auth\Services\AuthCRUDService;
use Modules\Auth\Services\AuthService;
use Ramsey\Uuid\Uuid;

class AuthController extends Controller
{
    public function __construct(
//        private AuthCRUDService $authService,
//        private UpdateAuthHandler $updateAuthHandler,
//        private DeleteAuthHandler $deleteAuthHandler,
    private AuthService $authService
    ) {
    }

    public function login(LoginRequest $request)
    {
        return $this->authService->login($request->createLoginDTO())->loginResponse();
    }

    public function logout(LogoutRequest $request)
    {
        return $this->authService->logout()->response("logout successful");
    }
    public function forgetPassword()
    {

    }
    public function resetPassword()
    {

    }


}
