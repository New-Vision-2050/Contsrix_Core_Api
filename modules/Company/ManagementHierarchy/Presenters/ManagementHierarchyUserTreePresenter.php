<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use App\Traits\CalculateTreeManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\User\Models\User;
use Modules\User\Presenters\UserPresenter;
use Modules\User\Presenters\UsersBranchPresenter;
use Ramsey\Uuid\Uuid;

class ManagementHierarchyUserTreePresenter extends AbstractPresenter
{
    use CalculateTreeManagementHierarchy;

    private ManagementHierarchy $managementHierarchy;

    private static bool $includeManagers = true;
    private static bool $includeDeputyManagers = true;
    private static bool $includeDirectChildren = true;
    private static bool $skipManagementMainNodes = false;
    
    // Track users that have already been included in the tree to prevent duplication
    private static array $includedUsers = [];

    public function __construct(ManagementHierarchy $managementHierarchy)
    {
        $this->managementHierarchy = $managementHierarchy;
    }

    /**
     * Reset the included users tracking array
     * Should be called at the start of each tree generation
     */
    public static function resetIncludedUsers(): void
    {
        self::$includedUsers = [];
    }

    /**
     * Check if a user has already been included in the tree
     */
    private static function isUserIncluded( $userId): bool
    {
        return in_array($userId, self::$includedUsers);
    }

    /**
     * Mark a user as included in the tree
     */
    private static function markUserAsIncluded( $userId): void
    {
        if (!self::isUserIncluded($userId)) {
            self::$includedUsers[] = $userId;
        }
    }

    public static function setIncludeManagers(bool $include): void
    {
        self::$includeManagers = $include;
    }

    public static function setIncludeDirectChildren(bool $include): void
    {
        self::$includeDirectChildren = $include;
    }

    public static function setIncludeDeputyManagers(bool $include): void
    {
        self::$includeDeputyManagers = $include;
    }

    public static function setSkipManagementMainNodes(bool $skip): void
    {
        self::$skipManagementMainNodes = $skip;
    }


    protected function present(bool $isListing = false): array
    {
        // Reset tracking for new tree generation (only at root level)
        if (empty(self::$includedUsers)) {
            self::resetIncludedUsers();
        }

        if ($this->managementHierarchy->type == "branch")
        {
            return $this->getUserChildren();
        }
        // Get manager of this hierarchy node
        $manager = $this->managementHierarchy->user;

        // If there's no manager, or if managers are excluded, return an object representing the hierarchy node itself
        if (!$manager || !self::$includeManagers) {
            return $this->presentHierarchyWithoutManager();
        }

        // Mark manager as included to prevent duplication
        self::markUserAsIncluded($manager->id);

        // Present the manager as the root of this subtree
        $result = (new UserPresenter($manager))->getData();

        $result['type'] = "manager";

        // Add hierarchy node info as metadata
        $result['hierarchy_info'] = [
            'id' => $this->managementHierarchy->id,
            'name' => $this->managementHierarchy->name,
            'type' => $this->managementHierarchy->type,
        ];
        if (self::$includeDeputyManagers) {
            $result['deputy_managers'] = UserPresenter::collection($this->managementHierarchy->deputyManagers);
        }
        // Add the manager's children (both direct reports and lower managers)
        $result['children'] = $this->getUserChildren();

        return $result;
    }


    private function presentHierarchyWithoutManager(): array
    {
        $result = [
            'id' => Uuid::uuid4()->toString(),
            'name' => 'No Manager Assigned',
            'hierarchy_info' => [
                'id' => $this->managementHierarchy->id,
                'name' => $this->managementHierarchy->name,
                'type' => $this->managementHierarchy->type,
            ],

        ];


        // Even without a manager, we should include the children users and lower managers
        $result['children'] = $this->getUserChildren();


        if (self::$includeDeputyManagers) {
            $result['deputy_managers'] = UserPresenter::collection($this->managementHierarchy->deputyManagers);
        }


        return $result;
    }


    private function getUserChildren(): array
    {
        $children = [];

        // Add direct reports (users directly assigned to this hierarchy) if enabled
        if (self::$includeDirectChildren && $this->managementHierarchy->type!="branch") {
            $directUsers = $this->managementHierarchy->directUserChildren ?? collect([]);
            foreach ($directUsers as $user) {
                // Skip this user if they're already included as a manager
                if (self::isUserIncluded($user->id)) {
                    continue;
                }

                // Present each direct report as a user with an empty children array
                $userData = (new UserPresenter($user))->getData();
                $userData['children'] = [];
                $userData['hierarchy_info'] = [
                    'id' => $this->managementHierarchy->id,
                    'name' => $this->managementHierarchy->name,
                    'type' => $this->managementHierarchy->type,
                ];
                $userData['type'] = "employee";

                // Mark this user as included to prevent future duplication
                self::markUserAsIncluded($user->id);

                $children[] = $userData;
            }
        }

        // Add managers of lower hierarchies
        $childHierarchies = $this->managementHierarchy->children;
        if ($childHierarchies->isNotEmpty()) {
            foreach ($childHierarchies as $childHierarchy) {
                if ((self::$skipManagementMainNodes && $childHierarchy->type === 'management' && $childHierarchy->is_main == 1||$childHierarchy->type=="branch")) {
                    // Skip this node but include its children in the result
                    if ($childHierarchy->children && $childHierarchy->children->count() > 0) {
                        // Process each child of the skipped node
                        foreach ($childHierarchy->children as $grandchild) {
                            // Add the manager as a child with their own subtree
                            $childPresenter = new self($grandchild);
                            $children[] = $childPresenter->getData();
                        }
                    }
                } else {
                    // Add the manager as a child with their own subtree
                    $childPresenter = new self($childHierarchy);
                    $children[] = $childPresenter->getData();
                }


            }
        }

        return $children;
    }

}
