<?php

namespace App\Notifications\Drivers\SMS;

use Illuminate\Support\Facades\Http;
use Modules\Setting\Models\Driver;

class MoraSms
{

    protected string $api_key;
    protected string $password;
    protected string $baseUrl;
    protected string $username;
    protected string $to;
    protected string $from;
    protected string $line;
    protected string $dryrun = 'no';

    /**
     * SmsMessage constructor.
     * @param array $lines
     */
    public function __construct($line = 'hello')
    {
        $this->line = $line;

        // Attempt to retrieve data from the drivers table
        $driver = Driver::query()->where('driver_type', 'sms')->where('name', 'mora')->first();

        if ($driver->config["SMS_MORA_KEY"] != "" && $driver->config["SMS_MORA_USER"] != "" && $driver->config["SMS_MORA_SENDER"] != "") {
            $this->api_key = $driver->config["SMS_MORA_KEY"];
            $this->username = $driver->config["SMS_MORA_USER"];
            $this->from = $driver->config["SMS_MORA_SENDER"];
            $this->baseUrl = config('services.mora_sms.base_url');
        } else {
            // Pull in config from the config/services.php file if not found in the drivers table
            $this->api_key = config('services.mora_sms.api_key');
            $this->baseUrl = config('services.mora_sms.base_url');
            $this->username = config('services.mora_sms.username');
            $this->from = config('services.mora_sms.sender');
        }
    }


    public function line($line = ''): self
    {
        $this->line = $line;

        return $this;
    }

    public function to($to): self
    {
        $this->to = $to;


        return $this;
    }

    public function from($from): self
    {
        $this->from = $from;

        return $this;
    }

    public function send(): mixed
    {


        $url = $this->baseUrl;
        $push_payload = array(
            "api_key" => $this->api_key,
            "username" => $this->username,
            "sender" => $this->from,
            "numbers" => $this->to,
            "message" => $this->line,
        );

        $response = Http::post($url, $push_payload);
        $output = $response->body();
        return $output;


    }


}
