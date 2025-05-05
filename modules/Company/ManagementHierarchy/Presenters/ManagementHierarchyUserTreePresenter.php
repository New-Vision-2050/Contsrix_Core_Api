<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use App\Traits\CalculateTreeManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\User\Models\User;
use Modules\User\Presenters\UserPresenter;

class ManagementHierarchyUserTreePresenter extends AbstractPresenter
{
    use CalculateTreeManagementHierarchy;

    private ManagementHierarchy $managementHierarchy;

    public function __construct(ManagementHierarchy $managementHierarchy)
    {
        $this->managementHierarchy = $managementHierarchy;
    }


    protected function present(bool $isListing = false): array
    {
        // Get the user associated with this hierarchy node (if any)
        $user = $this->managementHierarchy->user ?? null;

        return [
            // Include the user data if available
//            "user" => $user ? new UserPresenter($user) : null,

            // Include direct user children
            "directUserChildren" => UserPresenter::collection($this->managementHierarchy->directUserChildren),

            // Include hierarchical children
            "children" => ManagementHierarchyUserTreePresenter::collection($this->managementHierarchy->children),

            // Merged collection of all users (main user + direct children)
            "users" => $this->getMergedUsers(),
        ];
    }

    /**
     * Merge the main user with direct user children into a single collection
     *
     * @return array
     */
    private function getMergedUsers($users= [])
    {


        // Add the main user if available
        if (isset($this->managementHierarchy->user)) {
            $users[] = (new UserPresenter($this->managementHierarchy->user))->getData();
        }

        // Add all direct user children
        foreach ($this->managementHierarchy->directUserChildren as $directChild) {
            $users[] = (new UserPresenter($directChild))->getData();
        }
        if (isset($users[0]->name))
            return $users;
        return [];
    }
}
