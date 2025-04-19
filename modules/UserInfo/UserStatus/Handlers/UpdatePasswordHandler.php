<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserStatus\Handlers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Notifications\ResetPassword;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\UserStatus\Commands\UpdatePasswordCommand;
use Modules\UserInfo\UserStatus\Repositories\UserPasswordRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use ReflectionException;
use ReflectionProperty;

class UpdatePasswordHandler
{
    public function __construct(
        private UserPasswordRepository $repository,
        private UserRepository $userRepository,
    ) {
    }

    public function handle(UpdatePasswordCommand $command): void
    {
        try {
            $user = $this->userRepository->getUser($command->getId());
        } catch (ModelNotFoundException $e) {
            // Handle case where user is not found
            throw new \RuntimeException('User not found.');
        }

        $type = $command->getType();

        if ($type === 'automatic') {
            $newPassword = Str::random(10);
            $this->setCommandPassword($command, Hash::make($newPassword));
            $user->notify(new ResetPassword([
                'name' => $user->name,
                'email' => $user->email,
                'otp' => $newPassword,
                'minutes' => 10
            ]));
        } elseif ($type === 'manual') {
            $password = $command->getPassword();
            if ($password) {
                $this->setCommandPassword($command, Hash::make($password));
            }
        }

        $this->repository->updateUserStatus($command->getId(), $command->toArray());
    }

    /**
     * Use reflection to override the password property of the command.
     *
     * @throws ReflectionException
     */
    private function setCommandPassword(UpdatePasswordCommand $command, string $hashedPassword): void
    {
        $reflection = new \ReflectionClass($command);
        if ($reflection->hasProperty('password')) {
            /** @var ReflectionProperty $property */
            $property = $reflection->getProperty('password');
            $property->setAccessible(true);
            $property->setValue($command, $hashedPassword);
        } else {
            throw new \RuntimeException('Password property does not exist on command.');
        }
    }
}
