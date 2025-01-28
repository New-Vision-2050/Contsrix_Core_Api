<?php

namespace Modules\Auth\DataClasses;

class AuthMailData
{
    public $email;
    public $otp;
    public $name;

    public $minutes;
    public $url;

    public function __construct($email,$otp,$name,$minutes,$url)
    {
        $this->email = $email;
        $this->otp = $otp;
        $this->name = $name;
        $this->minutes = $minutes;
        $this->url = $url;

    }
    public function toArray() {
        return [
            'email' => $this->email,
            'otp' => $this->otp,
            'name' => $this->name,
           'minutes' => $this->minutes,
            "url" => $this->url
        ];
    }
    }
