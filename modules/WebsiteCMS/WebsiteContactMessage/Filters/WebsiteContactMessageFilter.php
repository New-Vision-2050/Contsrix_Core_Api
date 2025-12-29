<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteContactMessageFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', 'like', '%' . $name . '%');
    }

    public function phone($phone)
    {
        return $this->where('phone', 'like', '%' . $phone . '%');
    }

    public function email($email)
    {
        return $this->where('email', 'like', '%' . $email . '%');
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }

    public function message($message)
    {
        return $this->where('message', 'like', '%' . $message . '%');
    }

    public function search($message)
    {
        return $this->where('message', 'like', '%' . $message . '%')
           ->orWhere('name', 'like', '%' . $message . '%')
           ->orWhere('email', 'like', '%' . $message . '%');
    }
}
