<?php

namespace Modules\Auth\Services;

use BasePackage\Shared\Facade\Json;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\DTO\LoginDTO;
use Modules\Auth\Handlers\LogoutHandler;
use Modules\Auth\Repositories\AuthRepository;
use Modules\User\Presenters\UserPresenter;

class AuthService
{
    public function __construct(
//        private AuthRepository $repository,
        private $token,
        private LogoutHandler $logoutHandler
    )
    {
    }

    public function login(LoginDTO $authDTO)
    {

        $this->token = Auth::guard('api')->attempt($authDTO->toArray());
        return $this;
    }
    public function logout(){
        $this->logoutHandler->handle();
        return $this;
    }

    public function response($message)
    {
        return response([
            'status'=>true,
            "message"=>$message
        ]);
    }

    public function loginResponse()
    {
        if (!$this->token) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'token' => $this->token,

        ]);
    }
}
