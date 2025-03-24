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


    public function rules(): array
    {
        return [
            "config" => "required|array",
        ];
    }

    public function createUpdateDriverCommand()
    {
            $this->driverRepository  = app(DriverRepository::class);
            $driver = $this->driverRepository->find(Uuid::fromString($this->route('id')));
            if($driver->driver_type == "mail")
            {
                return new UpdateMailCommand(
                    id: Uuid::fromString($this->route('id')),
                    mailDriver: $this->config["MAIL_DRIVER"],
                    mailHost: $this->config["MAIL_HOST"],
                    mailPort: $this->config["MAIL_PORT"],
                    mailUsername: $this->config["MAIL_USERNAME"],
                    mailPassword: $this->config["MAIL_PASSWORD"],
                    mailEncryption: $this->config["MAIL_ENCRYPTION"],
                    mailAddress: $this->config["MAIL_FROM_ADDRESS"],
                    mailFromName: $this->config["MAIL_FROM_NAME"],
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



        throw new \Exception(__("validation.update-not-successful"), 500);

    }
}
