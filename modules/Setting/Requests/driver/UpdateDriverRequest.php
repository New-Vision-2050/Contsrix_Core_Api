<?php

declare(strict_types=1);

namespace Modules\Setting\Requests\driver;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Setting\Commands\Drivers\UpdateMailCommand;
use Modules\Setting\Commands\Drivers\UpdateMoraSMSCommand;
use Modules\Setting\Commands\UpdateLoginWayCommand;
use Modules\Setting\DTO\CreateLoginWayDTO;
use Modules\Setting\DTO\CreateSettingDTO;
use Modules\Setting\Repositories\DriverRepository;
use Ramsey\Uuid\Uuid;

class UpdateDriverRequest extends FormRequest
{
    private DriverRepository $driverRepository;
    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        $this->driverRepository  = app(DriverRepository::class);

        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    public function rules(): array
    {
        return [
            "config" => "required|array",
        ];
    }

    public function createUpdateDriverCommand()
    {
        try {
            $driver = $this->driverRepository->find(Uuid::fromString($this->route('id')));
            if($driver->driver_type == "mail")
            {
                return new UpdateMailCommand(
                    id: Uuid::fromString($this->route('id')),
                    mailMailer: $this->config["MAIL_MAILER"],
                    mailHost: $this->config["MAIL_HOST"],
                    mailPort: $this->config["MAIL_PORT"],
                    mailUsername: $this->config["MAIL_USERNAME"],
                    mailPassword: $this->config["MAIL_PASSWORD"]
                );
            }elseif ($driver->driver_type == "sms" && $driver->name == "mora")
            {
                return new UpdateMoraSMSCommand(
                    id: Uuid::fromString($this->route('id')),
                    smsMoraKey: $this->config["SMS_KEY"],
                    smsMoraUser: $this->config["SMS_USERNAME"],
                    smsMoraSender: $this->config["SMS_SENDER"],
                );
            }

        }
        catch (\Exception $e) {
            throw new \Exception(__("validation.update-not-successful"), 500);

        }


        throw new \Exception(__("validation.update-not-successful"), 500);

    }
}
