<?php

namespace App\Http\Controllers\HelperClasses;

use Illuminate\Support\Facades\Config;
use Modules\Setting\Models\Driver;

class MailClass
{

    protected $driver;
    protected $host;
    protected $port;
    protected $username;
    protected $password;
    protected $encryption;
    protected $fromName;
    protected $fromAddress;
    protected $setCongfigFromDB = false;


    public function __construct()
    {

        $emailConfig = Driver::where('name', "web mail")->first();
        if ($emailConfig
            && isset($emailConfig->config['MAIL_USERNAME'])
            && isset($emailConfig->config['MAIL_PASSWORD'])
            && $emailConfig->config['MAIL_USERNAME'] != ""
            && $emailConfig->config['MAIL_PASSWORD'] != ""
        ) {
            $this->setCongfigFromDB = 1;

            $this->driver = $emailConfig->config['MAIL_DRIVER'];
            $this->host = $emailConfig->config['MAIL_HOST'];
            $this->port = $emailConfig->config['MAIL_PORT'];
            $this->username = $emailConfig->config['MAIL_USERNAME'];
            $this->password = $emailConfig->config['MAIL_PASSWORD'];
            $this->encryption = $emailConfig->config['MAIL_ENCRYPTION'];
            $this->fromName = $emailConfig->config['MAIL_FROM_NAME'];
            $this->fromAddress = $emailConfig->config['MAIL_FROM_ADDRESS'];
        }


    }


    public function setConfig()
    {


        if ($this->setCongfigFromDB) {
            $config = array(
                'driver' => $this->driver,
                'host' => $this->host,
                'port' => $this->port,
                'from' => array('address' => $this->fromAddress, 'name' => $this->fromName),
                'encryption' => $this->encryption,
                'username' => $this->username,
                'password' => $this->password,
                'sendmail' => '/usr/sbin/sendmail -bs',
                'pretend' => false,
            );
            Config::set('mail', $config);
        }

    }


}
