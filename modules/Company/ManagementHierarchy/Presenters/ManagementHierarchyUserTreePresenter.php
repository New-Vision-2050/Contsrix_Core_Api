<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use App\Traits\CalculateTreeManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\User\Models\User;
use Modules\User\Presenters\UserPresenter;
use Illuminate\Support\Collection;

class ManagementHierarchyUserTreePresenter extends AbstractPresenter
{
    use CalculateTreeManagementHierarchy;

    private ManagementHierarchy $managementHierarchy;

    private static bool $includeManagers = true;
    private static bool $includeDirectChildren = true;

    public function __construct(ManagementHierarchy $managementHierarchy)
    {
        $this->managementHierarchy = $managementHierarchy;
    }

    public static function setIncludeManagers(bool $include): void
    {
        self::$includeManagers = $include;
    }

    public static function setIncludeDirectChildren(bool $include): void
    {
        self::$includeDirectChildren = $include;
    }


    protected function present(bool $isListing = false): array
    {
        // Get manager of this hierarchy node
        $manager = $this->managementHierarchy->user;

        // If there's no manager, or if managers are excluded, return an object representing the hierarchy node itself
        if (!$manager || !self::$includeManagers) {
            return $this->presentHierarchyWithoutManager();
        }

        // Present the manager as the root of this subtree
        $result = (new UserPresenter($manager))->getData();

        // Add hierarchy node info as metadata
        $result['hierarchy_info'] = [
            'id' => $this->managementHierarchy->id,
            'name' => $this->managementHierarchy->name,
            'type' => $this->managementHierarchy->type,
        ];

        // Add the manager's children (both direct reports and lower managers)
        $result['children'] = $this->getUserChildren();

        return $result;
    }


    private function presentHierarchyWithoutManager(): array
    {
        $result = [
            'id' => null,
            'name' => 'No Manager Assigned',
            'hierarchy_info' => [
                'id' => $this->managementHierarchy->id,
                'name' => $this->managementHierarchy->name,
                'type' => $this->managementHierarchy->type,
            ],
        ];

        // Even without a manager, we should include the children users and lower managers
        $result['children'] = $this->getUserChildren();

        return $result;
    }


    private function getUserChildren(): array
    {
        $children = [];

        // Add direct reports (users directly assigned to this hierarchy) if enabled
        if (self::$includeDirectChildren) {
            $directUsers = $this->managementHierarchy->directUserChildren ?? collect([]);
            foreach ($directUsers as $user) {
                // Present each direct report as a user with an empty children array
                $userData = (new UserPresenter($user))->getData();
                $userData['children'] = [];
                $children[] = $userData;
            }
        }

        // Add managers of lower hierarchies
        $childHierarchies = $this->managementHierarchy->children;
        if ($childHierarchies->isNotEmpty()) {
            foreach ($childHierarchies as $childHierarchy) {
                // Skip hierarchy nodes without managers to avoid duplication
                if (!$childHierarchy->user) {
                    // Instead, directly add their children to our current level
                    $this->addChildrenFromHierarchyWithoutManager($childHierarchy, $children);
                    continue;
                }

                // Add the manager as a child with their own subtree
                $childPresenter = new self($childHierarchy);
                $children[] = $childPresenter->getData();
            }
        }

        return $children;
    }


    private function addChildrenFromHierarchyWithoutManager(ManagementHierarchy $hierarchy, array &$children): void
    {
        // Add direct reports from this hierarchy if enabled
        if (self::$includeDirectChildren) {
            $directUsers = $hierarchy->directUserChildren ?? collect([]);
            foreach ($directUsers as $user) {
                $userData = (new UserPresenter($user))->getData();
                $userData['children'] = [];
                $userData['hierarchy_info'] = [
                    'id' => $hierarchy->id,
                    'name' => $hierarchy->name,
                    'type' => $hierarchy->type,
                ];
                $userData['deputy_managers'] = UserPresenter::collection($hierarchy->de)
                $children[] = $userData;
            }
        }

        // Recursively process child hierarchies
        $childHierarchies = $hierarchy->children;
        if ($childHierarchies->isNotEmpty()) {
            foreach ($childHierarchies as $childHierarchy) {
                if ($childHierarchy->user) {
                    // If this child has a manager, add them as a child
                    $childPresenter = new self($childHierarchy);
                    $children[] = $childPresenter->getData();
                } else {
                    // Otherwise, recursively add their children
                    $this->addChildrenFromHierarchyWithoutManager($childHierarchy, $children);
                }
            }
        }
    }
}
