<?php

namespace Modules\Auth\Services;

use Carbon\Carbon;
use Faker\Core\Uuid;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Commands\ResendOtpCommand;
use Modules\Auth\Commands\ResetPasswordCommand;
use Modules\Auth\DTO\GetLoginWaysDTO;
use Modules\Auth\DTO\LoginDTO;
use Modules\Auth\DTO\LoginStepDTO;
use Modules\Auth\DTO\LoginWithOtpDTO;
use Modules\Auth\DTO\QuestionVerificationDTO;
use Modules\Auth\Handlers\LogoutHandler;
use Modules\Auth\Handlers\MakeOtpHandler;
use Modules\Auth\Repositories\OtpRepository;
use Modules\Auth\Repositories\VerficationDataRepository;
use Modules\Auth\Repositories\VerficationQuestionRepository;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\Setting\Models\LoginWay;
use Modules\Setting\Models\LoginWayStep;
use Modules\Setting\Repositories\LoginWayRepository;
use Modules\Setting\Services\SettingCRUDService;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;
use Modules\User\Services\UserCRUDService;
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
        private VerficationQuestionRepository $verficationQuestionRepository
    )
    {
    }

    public function login(LoginDTO $authDTO)
    {
        $isContinueWithOTP = $this->settingCRUDService->getValue('continue_with_otp');
        if ($isContinueWithOTP) {
            $user = $this->userRepository->getUserByEmail($authDTO->getEmail());
            $this->sendOtpEmail->loginWithOtp($user->id);
            return [null, $user];
        }

        $token = JWTAuth::attempt($authDTO->toArray());
        if (!$token) {
            throw new \ErrorException(__("validation.invalid-credential"), 403);
        }
        $user = auth()->user();
        return [$token, $user];
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

        $token = JWTAuth::fromUser($user);

        return [$token, $user];

    }

    public function logout()
    {
        $this->logoutHandler->handle();
        return $this;
    }

    public function ResetPassword(ResetPasswordCommand $resetPasswordCommand)
    {
        if ((new Otp)->validate($resetPasswordCommand->getIdentifier(), $resetPasswordCommand->getOtp())->status == true) {
            $user = $this->userCRUDService->getUserByIdentifier($resetPasswordCommand->getIdentifier());

            $this->userRepository->updateUser($user->id, ["password" => $resetPasswordCommand->getPassword()]);

            return $this;
        }
        throw new \ErrorException(__("validation.invalid-otp"), 401);
    }

    public function resendOtp(ResendOtpCommand $resendOtpCommand)
    {

        try {
            $verficationData = $this->verficationDataRepository->findOneByOrFail(["token" => $resendOtpCommand->getToken()]);

        } catch (\Exception $e) {
            throw new \ErrorException("invalid token", 404);
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
                $types[] = $driver->driver_type;
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
        $loginWay = $this->loginWayRepository->findOneBy(["default" => 1]);
        $user = $this->userCRUDService->getUserByIdentifier($identifier);
        if($user->LoginWay != null)
        {
            $loginWay = $user->LoginWay;
        }
        return $loginWay;
    }

    public function getLoginWays(GetLoginWaysDTO $getLoginWaysDTO)
    {
        $loginWay = $this->getDefaultLoginWay($getLoginWaysDTO->getIdentifier());
        $step = $loginWay->loginWaySteps()->where("order", 1)->first();
        $user = $this->userCRUDService->getUserByIdentifier($getLoginWaysDTO->getIdentifier());

        $this->sendOtpByStep($step, $getLoginWaysDTO->getIdentifier());

        $token = $this->verficationDataRepository->createToken($user->id, ["order" => 1])->token;
        return [$loginWay, $token];

    }

    public function loginBySteps(LoginStepDTO $loginStepDTO)
    {
        try {
            $verficationData = $this->verficationDataRepository->findOneByOrFail(["token" => $loginStepDTO->getToken()]);

        } catch (\Exception $e) {
            throw new \ErrorException("invalid token", 404);
        }
        /**
         * @var $loginWay LoginWay
         * @var $user User
         * @var $step LoginWayStep
         */
        $loginWay = $this->getDefaultLoginWay($loginStepDTO->getIdentifier());
        $user = $this->userCRUDService->getUserByIdentifier($loginStepDTO->getIdentifier());

        //current step
        $step = $loginWay->loginWaySteps()->where("order", $verficationData->data["order"])->first();
        // if current step has otp then validate
        $this->checkOtpByStep($step, $loginStepDTO->getIdentifier(), $loginStepDTO->getPassword());
        // if current step has password then validate
        $this->checkPasswordByStep($step, $loginStepDTO->getIdentifier(), $loginStepDTO->getPassword());
        //delete token for current step
        $this->verficationDataRepository->deleteBy(["token" => $loginStepDTO->getToken()]);

        //get next step
        $step = $loginWay->loginWaySteps()->where("order", $verficationData->data["order"] + 1)->first();

        if ($step) {//if we have step
            $token = $this->verficationDataRepository->createToken($user->id, ["order" => $verficationData->data["order"] + 1])->token;

            $this->sendOtpByStep($step, $loginStepDTO->getIdentifier()); // if step has otp then send otp

            return [$loginWay, $token, $verficationData->data["order"] + 1];
        }
        //if no step else send token and authorize
        $token = JWTAuth::fromUser($user);

        return [$loginWay, $token, null];
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

}
