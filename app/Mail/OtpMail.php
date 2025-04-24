<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class OtpMail extends Mailable
{
    public $otpData;

    public function __construct($otpData)
    {
        $this->otpData = $otpData;
    }

    public function build()
    {
        return $this->subject(__('emails.change-your-email'))
                    ->view('emails.otp') // You can create an email view for OTP
                    ->with([
                        'otp' => $this->otpData['otp'],
                        'email' => $this->otpData['email'],
                    ]);
    }
}
