<?php

namespace Modules\CompanyUser\Events;

use Illuminate\Queue\SerializesModels;
use Modules\CompanyUser\Models\CompanyUser;

class UserCreated
{
    use SerializesModels;
    
    /**
     * @var CompanyUser
     */
    public $user;
    
    /**
     * @var string|null
     */
    public $companyId;
    
    /**
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param CompanyUser $user
     * @param string|null $companyId
     * @param array $data
     */
    public function __construct(CompanyUser $user, ?string $companyId = null, array $data = [])
    {
        $this->user = $user;
        $this->companyId = $companyId;
        $this->data = $data;
    }
}
