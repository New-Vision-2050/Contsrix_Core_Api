<?php

namespace Modules\Auth\Services;

use App\Exceptions\CustomException;
use Carbon\Carbon;
use Faker\Core\Uuid;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Commands\ResendOtpCommand;
use Modules\Auth\Commands\ResetPasswordCommand;
use Modules\Auth\DTO\GetLoginWaysDTO;
use Modules\Auth\DTO\LoginDTO;
use Modules\Auth\DTO\LoginStepAlternativeDTO;
use Modules\Auth\DTO\LoginStepDTO;
use Modules\Auth\DTO\LoginWithOtpDTO;
use Modules\Auth\DTO\QuestionVerificationDTO;
use Modules\Auth\DTO\ValidateOtpDTO;
use Modules\Auth\Handlers\LogoutHandler;
use Modules\Auth\Handlers\MakeOtpHandler;
use Modules\Auth\Repositories\OtpRepository;
use Modules\Auth\Repositories\VerficationDataRepository;
use Modules\Auth\Repositories\VerficationQuestionRepository;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Setting\Models\LoginWay;
use Modules\Setting\Models\LoginWayStep;
use Modules\Setting\Repositories\LoginWayRepository;
use Modules\Setting\Services\SettingCRUDService;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;
use Modules\User\Services\UserCRUDService;
use Modules\Auth\Enums\TokenAbility;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        private LogoutHandler                 $logoutHandler,
        private UserRepository                $userRepository,
        private OtpRepository                 $otpRepository,
        private SendOtpEmail                  $sendOtpEmail,
        private SettingCRUDService            $settingCRUDService,
        private LoginWayRepository            $loginWayRepository,
        private VerficationDataRepository     $verficationDataRepository,
        private UserCRUDService               $userCRUDService,
        private VerficationQuestionRepository $verficationQuestionRepository,
        private CompanyRepository             $companyRepository
    )
    {
    }

    public function login(LoginDTO $authDTO)
    {
        $isContinueWithOTP = $this->settingCRUDService->getValue('continue_with_otp');
        if ($isContinueWithOTP) {
            $user = $this->userRepository->getUserByIdentifier($authDTO->getEmail());
            $this->sendOtpEmail->loginWithOtp($user->id);
            return [null, $user];
        }
        $user = $this->userRepository->getUserByIdentifier($authDTO->getEmail());

        $accessToken = $this->makeAccessToken($user);
        if (!$accessToken) {
            throw new \ErrorException(__("validation.invalid-credential"), 403);
        }
        $refreshToken = $this->makeRefreshToken($user);
        return [$accessToken, $refreshToken, $user];
    }


    public function loginWithOtp(LoginWithOtpDTO $loginWithOtpDTO)
    {
        $isContinueWithOTP = $this->settingCRUDService->getValue('continue_with_otp');
        if (!$isContinueWithOTP) {
            throw new \ErrorException(__("validation.invalid-to-login-with-otp"), 403);
        }
        if ((new Otp)->validate($loginWithOtpDTO->getEmail(), $loginWithOtpDTO->getOtp())->status == false) {
            throw new \ErrorException(__("validation.invalid-otp"), 401);
        }


        $user = $this->userRepository->getUserByEmail($loginWithOtpDTO->getEmail());

        $accessToken = $this->makeAccessToken($user);
        $refreshToken = $this->makeRefreshToken($user);

        return [$accessToken, $refreshToken, $user];

    }

    public function logout()
    {
        $this->logoutHandler->handle();
        return $this;
    }


    public function validateOtp(ValidateOtpDTO $validateOtpDTO)
    {
        if ((new Otp)->validate($validateOtpDTO->getIdentifier(), $validateOtpDTO->getOtp())->status == true) {
            $user = $this->userCRUDService->getUserByIdentifier($validateOtpDTO->getIdentifier());

            $token = $this->verficationDataRepository->createToken($user->id, ["can_reset_password" => true])->token;
            return $token;
        }
        throw new \ErrorException(__("validation.invalid-otp"), 401);

    }

    public function ResetPassword(ResetPasswordCommand $resetPasswordCommand)
    {
        $token = $this->verficationDataRepository->findOneBy(["token" => $resetPasswordCommand->getToken()]);
        if (true || $token && isset($token->data["can_reset_password"]) && $token->data["can_reset_password"] == 1) {
            $user = $this->userCRUDService->getUserByIdentifier($resetPasswordCommand->getIdentifier());

            $this->userRepository->updateUser($user->id, ["password" => $resetPasswordCommand->getPassword()]);

            return $this;
        }
        throw new \ErrorException(__("validation.invalid-token"), 401);
    }

    public function resendOtp(ResendOtpCommand $resendOtpCommand)
    {

        try {
            $verficationData = $this->verficationDataRepository->findOneByOrFail(["token" => $resendOtpCommand->getToken()]);

        } catch (\Exception $e) {
            throw new \ErrorException(__("validation.invalid-token"), 404);
        }

        $loginWay = $this->getDefaultLoginWay($resendOtpCommand->getIdentifier());

        $step = $loginWay->loginWaySteps()->where("order", $verficationData->data["order"])->first();

        if ($step->login_option != "otp") {
            throw new \ErrorException(__("validation.can-not-resend-otp"), 400);
        }

        $otp = $this->otpRepository->getOtpDataByIdentifier($resendOtpCommand->getIdentifier());

        if (Carbon::parse($otp->created_at)->diffInMinutes(Carbon::now()) < 3) {
            throw new \ErrorException(__("validation.can-not-resend-before", ["minute" => 3]), 400);

        }

        $this->sendOtpByStep($step, $resendOtpCommand->getIdentifier());

    }

    private function sendOtpByStep($step, $identifier)
    {
        if ($step->login_option == "otp") {
            $types = [];
            foreach ($step->drivers as $driver) {
                $types[] = $driver;
            }
            $this->sendOtpEmail->loginStepOtp($identifier, $types);
        }
    }

    private function checkOtpByStep($step, $identifier, $otp)
    {
        if ($step->login_option == "otp" && (new Otp)->validate($identifier, $otp)->status == false) {
            throw new \Exception(__("validation.invalid-otp"), 401);
        }
        return true;
    }


    private function checkPasswordByStep($step, $identifier, $password)
    {
        $user = $this->userCRUDService->getUserByIdentifier($identifier);

        if ($step->login_option == "password" && (!$user || !password_verify($password, $user->password))) {
            throw new \ErrorException(__("validation.invalid-credential"), 401);
        }
        return true;
    }

    private function getDefaultLoginWay($identifier)
    {
        $loginWay = $this->loginWayRepository->findOneByWithRelations(["default" => 1], ["loginWaySteps"]);
        $user = $this->userCRUDService->getUserByIdentifier($identifier);
        if ($user->login_way_id != null) {
            $loginWay = $this->loginWayRepository->findOneByWithRelations(["id" => $user->login_way_id], ["loginWaySteps"]);
        }
        return $loginWay;
    }

    public function getLoginWays(GetLoginWaysDTO $getLoginWaysDTO)
    {

        $loginWay = $this->getDefaultLoginWay($getLoginWaysDTO->getIdentifier());
        $step = $loginWay->loginWaySteps()->where("order", 1)->first();
        $user = $this->userCRUDService->getUserByIdentifier($getLoginWaysDTO->getIdentifier());
        $firstLogin = $user->password == null ? 1 : 0;

        if ($user->password == null) {
            $this->sendOtpEmail->resetPassword($getLoginWaysDTO->getIdentifier(), $firstLogin);
            return [$loginWay->id, null, $step, 1, $firstLogin];
        }

        $this->sendOtpByStep($step, $getLoginWaysDTO->getIdentifier());
        $token = $this->verficationDataRepository->createToken($user->id, ["order" => 1, "login_way" => $loginWay])->token;


        return [$loginWay->id, $token, $step, 0, $firstLogin];

    }

    private function getLoginStepAndNextStepFromToken($token)
    {
        $verficationData = $this->verficationDataRepository->findOneByOrFail(["token" => $token]);
        $step = collect($verficationData->data["login_way"]["login_way_steps"])->filter(function ($item) use ($verficationData) {
            return $item['order'] == $verficationData->data["order"];
        })->first();
        $nextStep = collect($verficationData->data["login_way"]["login_way_steps"])->filter(function ($item) use ($verficationData) {
            return $item['order'] == $verficationData->data["order"] + 1;
        })->first();
        $nextStep = $nextStep == null ? null : (object)$nextStep;
        return [(object)$step, $nextStep];

    }

    private function updateLoginStep($token, $loginOption)
    {
        $verficationData = $this->verficationDataRepository->findOneByOrFail(["token" => $token]);
        $updatedSteps = collect($verficationData->data["login_way"]["login_way_steps"])->map(function ($item) use ($verficationData, $loginOption) {
            if ($item['order'] == $verficationData->data["order"]) {
                if ($loginOption == "sms" || $loginOption == "mail" || $loginOption == "social") {
                    $item["login_option"] = "otp";
                    $item["drivers"] = [$loginOption];
                } elseif ($loginOption == "password") {
                    $item["login_option"] = "password";
                    $item["drivers"] = null;
                }

            }
            return $item;
        });
        $data = $verficationData->data;
        $data['login_way']['login_way_steps'] = $updatedSteps;
        $verficationData->setAttribute('data', $data);
        $verficationData->save();

        return $verficationData;
    }

    public function loginBySteps(LoginStepDTO $loginStepDTO)
    {
        try {
            $verficationData = $this->verficationDataRepository->findOneByOrFail(["token" => $loginStepDTO->getToken()]);

        } catch (\Exception $e) {
            throw new \ErrorException(__("validation.invalid-token"), 404);
        }
        /**
         * @var $loginWay LoginWay
         * @var $user User
         * @var $step LoginWayStep
         */
        $loginWay = $verficationData->data["login_way"];

        $user = $this->userCRUDService->getUserByIdentifier($loginStepDTO->getIdentifier());

        //current step
        [$step, $nextStep] = $this->getLoginStepAndNextStepFromToken($loginStepDTO->getToken());

        // if current step has otp then validate
        $this->checkOtpByStep($step, $loginStepDTO->getIdentifier(), $loginStepDTO->getPassword());
        // if current step has password then validate
        $this->checkPasswordByStep($step, $loginStepDTO->getIdentifier(), $loginStepDTO->getPassword());

        //delete token for current step
        $this->verficationDataRepository->deleteBy(["token" => $loginStepDTO->getToken()]);

        //get next step

        if ($nextStep) {//if we have step
            $token = $this->verficationDataRepository->createToken($user->id, ["order" => $verficationData->data["order"] + 1, "login_way" => $loginWay])->token;

            $this->sendOtpByStep($nextStep, $loginStepDTO->getIdentifier()); // if step has otp then send otp

            return [$loginWay["id"], $token, $nextStep, null];
        }
        //if no step else send token and authorize
        $accessToken = $this->makeAccessToken($user);
        $refreshToken = $this->makeRefreshToken($user);

        return [$loginWay["id"], $accessToken, $nextStep, $refreshToken];
    }

    public function checkQuestionAnswer(QuestionVerificationDTO $questionVerificationDTO)
    {
        $user = $this->userCRUDService->getUserByIdentifier($questionVerificationDTO->getIdentifier());

        $questionAndAnswers = $this->verficationQuestionRepository->findBy(["user_id" => $user->id]);
        if ($questionAndAnswers->count() == 0) {
            throw new \ErrorException(__("validation.you-must-set-your-answers"), 401);
        }
        if ($questionAndAnswers->count() != count($questionVerificationDTO->getquestionsAndAnswers())) {
            throw new \ErrorException(__("validation.all-questions-are-required"), 428);
        }
        foreach ($questionVerificationDTO->getquestionsAndAnswers() as $questionAndAnswer) {
            $hashedCorrectAnswer = $questionAndAnswers->where("question_id", $questionAndAnswer["question_id"])->first()->answer;
            if (!Hash::check($questionAndAnswer["answer"], $hashedCorrectAnswer)) {
                return [false, null];
            }
        }
        $verficationData = $this->verficationDataRepository->createToken($user->id, ["change_email" => 1]);
        return [true, $verficationData->token];
    }


    public function loginStepAlternative(LoginStepAlternativeDTO $alternativeDTO)
    {

        try {
            $verficationData = $this->verficationDataRepository->findOneByOrFail(["token" => $alternativeDTO->getToken()]);

        } catch (\Exception $e) {
            throw new \ErrorException(__("validation.invalid-token"), 404);
        }
        $this->updateLoginStep($alternativeDTO->getToken(), $alternativeDTO->getLoginOption());
        [$step, $nextStep] = $this->getLoginStepAndNextStepFromToken($alternativeDTO->getToken());
        $this->sendOtpByStep($step, $alternativeDTO->getIdentifier());

        return [$verficationData->data["login_way"]["id"], $verficationData->token, $step];

    }

    public function getDataForLoginAsAdmin($companyId)
    {
        $company = $this->companyRepository->findOneBy(["id" => $companyId]);
        if($company->is_active == 0)
        {
             throw new CustomException(__("validation.company-not-active"),403);
        }
        tenancy()->end();
        tenancy()->initialize($company->id);
        $user = $this->userCRUDService->getUserBy(['email' => "admin@constrix-nv.com"]);
        if (empty($user)) {
            $user = User::firstOrCreate(
                ['email' => 'admin@constrix-nv.com',],
                [
                    'name' => 'Admin',
                    'email' => 'admin@constrix-nv.com',
                    "phone" => "966542138116",
                    "phone_code" => "966",
                    'password' => "Test1234",
                    "global_company_user_id" => CompanyUser::query()->withoutParentModel(
                        )->where("email", "admin@constrix-nv.com")->first()->global_id,
                    "company_id" => tenant("id"),
                ]
            );
            $user->assignRole('super-admin');
        }

        $token = $this->verficationDataRepository->createToken($user->id, ["login_as_admin" => 1])->token;

        return ["url" => "https://" . $company->domains()->first()->domain, "token" => $token];


    }

    public function loginAsAdmin($token)
    {


        $verficationData = $this->verficationDataRepository->validateToken($token);


        $user = $this->userRepository->findOneBy(["id" => $verficationData->user_id]);
        $accessToken = $this->makeAccessToken($user);
        $refreshToken = $this->makeRefreshToken($user);

        return [$accessToken, $refreshToken, $user];


    }

    public function refreshToken(): string
    {
        $token = JWTAuth::getToken();
        $payload = JWTAuth::getPayload($token);

        if ($payload->get('token_ability') !== TokenAbility::ISSUE_ACCESS_TOKEN->value) {
            throw new \ErrorException(__("validation.invalid-token"), 401);
        }

        $userId = $payload->get('sub');
        $user = $this->userRepository->findOneBy(['id' => $userId]);

        return $this->makeAccessToken($user);
    }

    private function makeAccessToken($user): string
    {
        JWTAuth::manager()->setTTL(config('jwt.ac_expiration'));
        return JWTAuth::claims(['token_ability' => TokenAbility::ACCESS_API->value])
            ->fromUser($user);
    }

    private function makeRefreshToken($user): string
    {
        JWTAuth::manager()->setTTL(config('jwt.rt_expiration'));
        return JWTAuth::claims(['token_ability' => TokenAbility::ISSUE_ACCESS_TOKEN->value])
            ->fromUser($user);
    }

}
