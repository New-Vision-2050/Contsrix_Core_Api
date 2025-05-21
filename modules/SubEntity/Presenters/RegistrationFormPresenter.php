<?php

declare(strict_types=1);

namespace Modules\SubEntity\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\SubEntity\Models\RegistrationForm;

class RegistrationFormPresenter extends AbstractPresenter
{
    private RegistrationForm $registrationForm;

    public function __construct(RegistrationForm $registrationForm)
    {
        $this->registrationForm = $registrationForm;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->registrationForm->id,
            'name' => $this->registrationForm->name[app()->getLocale()],
            'slug' => $this->registrationForm->slug,
            'company_user_role_map' => $this->registrationForm->company_user_role_map,
        ];
    }
}
